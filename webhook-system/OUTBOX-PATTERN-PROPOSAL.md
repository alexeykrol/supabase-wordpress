# 🏗️ Transactional Outbox Pattern - Future Implementation

**Status:** Proposal (Future Enhancement)
**Priority:** Medium-High
**GitHub Issue:** [#11](https://github.com/alexeykrol/supabase-wordpress/issues/11)

---

## 📋 Executive Summary

This document proposes migrating from **direct webhook triggers** to **Transactional Outbox Pattern** for:
- ✅ **Zero event loss** guarantee
- ✅ **Multi-event support** (user.registered, pair.created, subscription.updated, etc.)
- ✅ **Automatic retry** with exponential backoff
- ✅ **Scalability** for high-volume event processing

---

## 🤔 Why Outbox Pattern?

### Current Architecture (v0.8.0) Limitations:

```
User Registration
  ↓
INSERT wp_user_registrations
  ↓
Trigger → Edge Function (immediate call)
  ↓
IF Edge Function fails BEFORE logging → EVENT LOST ❌
```

**Problems:**
- ❌ **Tight coupling:** Business logic tied to webhook delivery
- ❌ **Single event type:** Only supports user registration
- ❌ **No automatic retry:** Failed webhooks need manual intervention
- ❌ **Limited scalability:** Cannot batch process events

### Outbox Pattern Benefits:

```
Any Event (registration, update, deletion, etc.)
  ↓
INSERT into source table
  ↓
Trigger → INSERT into webhook_outbox (always succeeds)
  ↓
Background Worker (Scheduled Edge Function)
  ↓
Process events in batches → retry failed → update status
  ↓
GUARANTEED DELIVERY ✅
```

**Advantages:**
- ✅ **Zero event loss:** Events persisted before processing
- ✅ **Multi-event:** Support unlimited event types
- ✅ **Automatic retry:** Exponential backoff for failures
- ✅ **Batch processing:** Handle 1000+ events efficiently
- ✅ **Decoupled:** Business logic independent of webhooks

---

## 📦 Database Schema

### Core Outbox Table

```sql
CREATE TABLE webhook_outbox (
  -- Identity
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

  -- Event Classification
  event_type TEXT NOT NULL,
    -- Examples:
    --   'user.registered', 'user.updated', 'user.deleted'
    --   'pair.created', 'pair.updated', 'pair.deleted'
    --   'subscription.created', 'subscription.cancelled'
    --   'payment.succeeded', 'payment.failed'

  aggregate_type TEXT NOT NULL,  -- 'user', 'pair', 'subscription', 'payment'
  aggregate_id UUID NOT NULL,    -- ID of the entity that triggered event

  -- Payload
  payload JSONB NOT NULL,        -- Event-specific data (flexible schema)

  -- Processing State
  status TEXT NOT NULL DEFAULT 'pending',
    -- Values: 'pending', 'processing', 'sent', 'failed', 'dead_letter'

  retry_count INTEGER DEFAULT 0,
  max_retries INTEGER DEFAULT 5,
  next_retry_at TIMESTAMPTZ,     -- When to retry (exponential backoff)

  -- Audit Trail
  created_at TIMESTAMPTZ DEFAULT NOW(),
  processed_at TIMESTAMPTZ,

  -- Error Handling
  error_message TEXT,
  error_details JSONB,

  -- Constraints
  CONSTRAINT valid_status CHECK (
    status IN ('pending', 'processing', 'sent', 'failed', 'dead_letter')
  ),
  CONSTRAINT valid_retry_count CHECK (retry_count >= 0 AND retry_count <= max_retries)
);

-- Indexes for Performance
CREATE INDEX idx_outbox_pending ON webhook_outbox(status, next_retry_at)
WHERE status IN ('pending', 'failed');

CREATE INDEX idx_outbox_event_type ON webhook_outbox(event_type, created_at DESC);
CREATE INDEX idx_outbox_aggregate ON webhook_outbox(aggregate_type, aggregate_id);
CREATE INDEX idx_outbox_dead_letter ON webhook_outbox(created_at DESC)
WHERE status = 'dead_letter';

-- Partitioning (for high volume)
-- CREATE TABLE webhook_outbox_2025_01 PARTITION OF webhook_outbox
-- FOR VALUES FROM ('2025-01-01') TO ('2025-02-01');
```

---

## 🔄 Supported Event Types

### User Events
```json
{
  "event_type": "user.registered",
  "aggregate_type": "user",
  "aggregate_id": "user-uuid",
  "payload": {
    "user_id": "supabase-uuid",
    "email": "user@example.com",
    "registration_url": "/services/",
    "thankyou_page_url": "/services-thankyou/",
    "pair_id": "pair-uuid-or-null",
    "registered_at": "2025-10-26T12:00:00Z"
  }
}
```

### Registration Pair Events
```json
{
  "event_type": "pair.created",
  "aggregate_type": "pair",
  "aggregate_id": "pair-uuid",
  "payload": {
    "pair_id": "uuid",
    "registration_url": "/services/",
    "thankyou_page_url": "/services-thankyou/",
    "created_by": "admin-user-id",
    "created_at": "2025-10-26T12:00:00Z"
  }
}
```

### Future: Subscription Events
```json
{
  "event_type": "subscription.created",
  "aggregate_type": "subscription",
  "aggregate_id": "subscription-uuid",
  "payload": {
    "user_id": "user-uuid",
    "plan": "premium",
    "amount": 29.99,
    "currency": "USD",
    "billing_cycle": "monthly",
    "started_at": "2025-10-26T12:00:00Z"
  }
}
```

### Future: Payment Events
```json
{
  "event_type": "payment.succeeded",
  "aggregate_type": "payment",
  "aggregate_id": "payment-uuid",
  "payload": {
    "user_id": "user-uuid",
    "subscription_id": "subscription-uuid",
    "amount": 29.99,
    "currency": "USD",
    "payment_method": "card",
    "transaction_id": "stripe-txn-id",
    "paid_at": "2025-10-26T12:00:00Z"
  }
}
```

---

## ⚙️ Implementation Components

### 1. Event Creation Triggers

```sql
-- User Registration Event
CREATE OR REPLACE FUNCTION create_user_registered_event()
RETURNS TRIGGER AS $$
BEGIN
  INSERT INTO webhook_outbox (
    event_type,
    aggregate_type,
    aggregate_id,
    payload
  ) VALUES (
    'user.registered',
    'user',
    NEW.id,
    jsonb_build_object(
      'user_id', NEW.user_id,
      'email', NEW.user_email,
      'registration_url', NEW.registration_url,
      'thankyou_page_url', NEW.thankyou_page_url,
      'pair_id', NEW.pair_id,
      'registered_at', NEW.registered_at
    )
  );

  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER on_user_registration_create_event
AFTER INSERT ON wp_user_registrations
FOR EACH ROW
EXECUTE FUNCTION create_user_registered_event();
```

### 2. Outbox Processor (Scheduled Edge Function)

```typescript
// supabase/functions/process-outbox-events/index.ts

import { serve } from "https://deno.land/std@0.168.0/http/server.ts"
import { createClient } from "https://esm.sh/@supabase/supabase-js@2.39.0"

const BATCH_SIZE = 100
const MAX_RETRIES = 5
const RETRY_DELAYS = [60, 300, 900, 3600, 7200] // 1m, 5m, 15m, 1h, 2h

serve(async (req: Request) => {
  const supabase = createClient(
    Deno.env.get("SUPABASE_URL")!,
    Deno.env.get("SUPABASE_SERVICE_ROLE_KEY")!
  )

  // Fetch pending events
  const { data: events, error } = await supabase
    .from("webhook_outbox")
    .select("*")
    .in("status", ["pending", "failed"])
    .or(`next_retry_at.is.null,next_retry_at.lte.${new Date().toISOString()}`)
    .order("created_at", { ascending: true })
    .limit(BATCH_SIZE)

  if (error || !events?.length) {
    return new Response(JSON.stringify({ processed: 0 }), { status: 200 })
  }

  // Process each event
  const results = await Promise.allSettled(
    events.map(event => processEvent(event, supabase))
  )

  return new Response(
    JSON.stringify({
      processed: events.length,
      succeeded: results.filter(r => r.status === "fulfilled").length,
      failed: results.filter(r => r.status === "rejected").length
    }),
    { status: 200 }
  )
})

async function processEvent(event: any, supabase: any) {
  // Mark as processing
  await supabase
    .from("webhook_outbox")
    .update({ status: "processing" })
    .eq("id", event.id)

  try {
    // Send webhook to n8n/make
    const webhookUrl = Deno.env.get("WEBHOOK_URL")!
    const response = await fetch(webhookUrl, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        event: event.event_type,
        data: event.payload,
        timestamp: new Date().toISOString()
      })
    })

    if (response.ok) {
      // Success
      await supabase
        .from("webhook_outbox")
        .update({
          status: "sent",
          processed_at: new Date().toISOString()
        })
        .eq("id", event.id)
    } else {
      throw new Error(`HTTP ${response.status}: ${await response.text()}`)
    }
  } catch (error) {
    // Handle failure
    const newRetryCount = event.retry_count + 1

    if (newRetryCount >= MAX_RETRIES) {
      // Move to dead-letter queue
      await supabase
        .from("webhook_outbox")
        .update({
          status: "dead_letter",
          retry_count: newRetryCount,
          error_message: error.message,
          processed_at: new Date().toISOString()
        })
        .eq("id", event.id)
    } else {
      // Schedule retry with exponential backoff
      const nextRetry = new Date()
      nextRetry.setSeconds(nextRetry.getSeconds() + RETRY_DELAYS[newRetryCount - 1])

      await supabase
        .from("webhook_outbox")
        .update({
          status: "failed",
          retry_count: newRetryCount,
          next_retry_at: nextRetry.toISOString(),
          error_message: error.message
        })
        .eq("id", event.id)
    }

    throw error
  }
}
```

### 3. Scheduled Execution (cron)

```sql
-- In Supabase Dashboard → Database → Cron Jobs
SELECT cron.schedule(
  'process-outbox-events',
  '*/10 * * * *',  -- Every 10 seconds
  $$
  SELECT net.http_post(
    url := current_setting('app.settings.edge_function_url') || '/process-outbox-events',
    headers := jsonb_build_object(
      'Authorization', 'Bearer ' || current_setting('app.settings.service_role_key')
    )
  );
  $$
);
```

---

## 🎨 WordPress Admin UI

### New Tab: "Event Monitor"

**Features:**
1. **Event List Table:**
   - Columns: Event Type, Status, Retry Count, Created At, Error
   - Filters: Status, Event Type, Date Range
   - Actions: Retry, View Payload, Delete

2. **Dashboard Widgets:**
   - Events processed today: 1,234
   - Failed events: 12
   - Dead-letter queue: 3
   - Average processing time: 2.4s

3. **Actions:**
   - "Retry Failed Events" button
   - "Clear Dead-Letter Queue" button
   - "View Event Payload" modal

---

## 📊 Migration Strategy

### Phase 1: Parallel Run (Week 1-2)
- Deploy outbox alongside current system
- Both systems process events
- Compare results (should be identical)
- Monitor performance metrics

### Phase 2: Gradual Migration (Week 3)
- Route 10% traffic to outbox → monitor
- Route 50% traffic → monitor
- Route 100% traffic → monitor

### Phase 3: Deprecation (Week 4)
- Disable old trigger system
- Remove direct Edge Function calls
- Archive old webhook_logs

### Phase 4: Cleanup (Week 5+)
- Remove old code
- Update documentation
- Optimize queries based on production data

---

## 🎯 Success Criteria

### Reliability
- ✅ 0% event loss (all events reach outbox)
- ✅ 99.9% webhook delivery rate (with retries)
- ✅ < 0.1% dead-letter queue rate

### Performance
- ✅ < 10 sec average event processing time
- ✅ < 1 min P99 processing time
- ✅ Handle 10,000 events/day without degradation

### Observability
- ✅ Full audit trail for all events
- ✅ Real-time dashboard showing event status
- ✅ Alerts for high failure rates

---

## 📚 References

- [Microservices Pattern: Transactional Outbox](https://microservices.io/patterns/data/transactional-outbox.html)
- [Debezium Outbox Pattern](https://debezium.io/blog/2019/02/19/reliable-microservices-data-exchange-with-the-outbox-pattern/)
- [AWS: Event-Driven Architecture Best Practices](https://aws.amazon.com/event-driven-architecture/)
- [Supabase Edge Functions: Scheduled Invocations](https://supabase.com/docs/guides/functions/schedule-functions)

---

## 💬 Next Steps

1. **Review & Discuss:** Team review of this proposal
2. **POC:** Build proof-of-concept for 1 event type
3. **Test:** Load testing with 10,000 events
4. **Deploy:** Gradual rollout as described above
5. **Monitor:** Track success metrics for 1 month
6. **Iterate:** Optimize based on production data

---

**Last Updated:** 2025-10-26
**Author:** Claude + Alex
**Status:** Proposal (awaiting approval)
