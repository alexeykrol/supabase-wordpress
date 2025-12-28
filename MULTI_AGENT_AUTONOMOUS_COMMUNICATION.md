# Multi-Agent Autonomous Communication Protocol

**Created:** 2025-12-24
**Authors:** Alexey Krol + Claude Sonnet 4.5
**Status:** Concept / Design Phase
**Target:** Claude Code Framework / Independent Tool

---

## Executive Summary

**Problem:** When working with multiple Claude instances across different projects, valuable inter-AI discussions require manual copy-paste by the user, acting as a "message courier."

**Solution:** Enable autonomous AI-to-AI communication via local HTTP servers and shared files, allowing two (or more) Claude instances to hold productive discussions until reaching consensus, then present results to the user for review.

**Impact:**
- User becomes architect/critic instead of message courier
- Faster consensus through adversarial collaboration
- Scalable to N agents with different roles
- Enables complex multi-agent problem-solving

---

## Table of Contents

1. [Problem Statement](#problem-statement)
2. [Current Manual Process](#current-manual-process)
3. [Proposed Solution](#proposed-solution)
4. [Technical Architecture](#technical-architecture)
5. [Protocol Design](#protocol-design)
6. [Safety Mechanisms](#safety-mechanisms)
7. [Implementation Plan](#implementation-plan)
8. [Use Cases](#use-cases)
9. [Future Extensions](#future-extensions)
10. [Appendix: Original Conversation](#appendix-original-conversation)

---

## Problem Statement

### Context

When working on complex problems across multiple projects (e.g., developing a Framework while using it in a host project like supabase-bridge), a single Claude instance has limitations:

- **Context isolation:** Claude in project A doesn't see project B's context
- **Perspective bias:** Single AI may miss alternative approaches
- **No adversarial review:** No built-in peer review mechanism

### Current Workaround

User manually facilitates dialog between two Claude instances:

1. Claude A asks a question
2. User copies question
3. User pastes to Claude B
4. Claude B responds
5. User copies response
6. User pastes back to Claude A
7. Repeat until consensus

**This works but is slow and tedious.**

### Ideal Scenario

Two Claude instances:
- Communicate autonomously
- Exchange questions/answers automatically
- Debate approaches
- Reach consensus
- Present final proposal to user for review

**User role shifts from courier to architect/critic.**

---

## Current Manual Process

### Example: Framework Improvement Discussion

**Setup:**
- Terminal 1: Claude in `supabase-bridge/` (knows production context)
- Terminal 2: Claude in `claude-code-starter/` (knows framework internals)

**Process:**
1. User to Claude A: "How should we implement AI Developer Profiling in the framework?"
2. Claude A responds with approach X
3. User copies response
4. User to Claude B: "Claude from supabase-bridge suggests approach X. What do you think?"
5. Claude B critiques, suggests modifications
6. User copies critique back to Claude A
7. Claude A refines approach
8. ... (repeat 5-10 times)
9. Finally reach consensus

**Time cost:** 15-30 minutes of manual copy-paste for a 5-minute AI discussion.

---

## Proposed Solution

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    User (Architect/Critic)                  │
│                                                             │
│  • Initiates topic                                          │
│  • Monitors conversation (tail -f)                          │
│  • Reviews consensus                                        │
│  • Approves or requests revisions                           │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ├─────────────────┬────────────────────────┐
                   ▼                 ▼                        ▼
        ┌──────────────────┐ ┌──────────────────┐  ┌─────────────────┐
        │   Claude A       │ │   Claude B       │  │  Shared File    │
        │ (supabase-bridge)│ │  (framework)     │  │  /tmp/dialog.md │
        │                  │ │                  │  │                 │
        │ HTTP :3001       │ │ HTTP :3002       │  │ Turn-based log  │
        │                  │ │                  │  │ Consensus marker│
        └────────┬─────────┘ └─────────┬────────┘  └─────────────────┘
                 │                     │
                 │  POST /notify       │
                 ├────────────────────►│
                 │                     │
                 │◄────────────────────┤
                 │  POST /notify       │
                 │                     │
                 └─────────────────────┘
                   Autonomous Dialog
```

### Key Components

1. **Shared Conversation File** (`/tmp/claude-dialog.md`)
   - Append-only markdown log
   - Turn marker: `TURN: A` or `TURN: B`
   - Consensus marker: `[CONSENSUS_REACHED]`

2. **HTTP Notification Servers**
   - Claude A: `http://localhost:3001`
   - Claude B: `http://localhost:3002`
   - Endpoint: `POST /notify` (wakes up Claude for their turn)

3. **Turn-Based Protocol**
   - Only Claude whose turn it is can write
   - After writing, flips turn and notifies other
   - Prevents race conditions

4. **Safety Mechanisms**
   - Max turns limit (e.g., 20)
   - Timeout (e.g., 5 minutes)
   - Deadlock detection
   - Emergency stop signal

---

## Technical Architecture

### Variant 1: HTTP Servers + Shared File ⭐ (Recommended)

**Why recommended:**
- Clear turn-taking protocol (no race conditions)
- HTTP is standard, debuggable (can use `curl` to test)
- Full conversation history in one file
- Easy to monitor (`tail -f /tmp/claude-dialog.md`)
- Extensible to N agents

**Implementation:**

```javascript
// .claude/scripts/dialog-server.js

const express = require('express');
const fs = require('fs');
const path = require('path');

const PORT = process.env.CLAUDE_PORT || 3001;
const MY_ID = process.env.CLAUDE_ID || 'A';
const OTHER_PORT = MY_ID === 'A' ? 3002 : 3001;
const DIALOG_FILE = '/tmp/claude-dialog.md';
const MAX_TURNS = 20;
const TIMEOUT_MS = 5 * 60 * 1000; // 5 minutes

const app = express();
app.use(express.json());

// Notification endpoint - "It's your turn!"
app.post('/notify', async (req, res) => {
  console.log(`[Claude ${MY_ID}] Received turn notification`);

  try {
    // Read conversation
    const conversation = fs.readFileSync(DIALOG_FILE, 'utf8');

    // Check if it's really my turn
    if (!conversation.includes(`TURN: ${MY_ID}`)) {
      return res.json({status: 'not_my_turn'});
    }

    // Check for consensus/deadlock
    if (conversation.includes('[CONSENSUS_REACHED]')) {
      console.log('[Claude ${MY_ID}] Consensus reached, stopping');
      return res.json({status: 'consensus_reached'});
    }

    // Check turn limit
    const turnCount = (conversation.match(/## Turn \d+/g) || []).length;
    if (turnCount >= MAX_TURNS) {
      appendToDialog(`\n[MAX_TURNS_REACHED]\n\nStopping after ${MAX_TURNS} turns.`);
      return res.json({status: 'max_turns'});
    }

    // Process and respond
    const response = await processAndRespond(conversation);

    // Write response
    appendToDialog(response);

    // Flip turn
    const updated = fs.readFileSync(DIALOG_FILE, 'utf8');
    const flipped = updated.replace(`TURN: ${MY_ID}`, `TURN: ${MY_ID === 'A' ? 'B' : 'A'}`);
    fs.writeFileSync(DIALOG_FILE, flipped);

    // Notify other Claude
    const otherClaude = MY_ID === 'A' ? 'B' : 'A';
    console.log(`[Claude ${MY_ID}] Notifying Claude ${otherClaude}`);

    fetch(`http://localhost:${OTHER_PORT}/notify`, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({from: MY_ID})
    }).catch(err => console.error('Notify failed:', err));

    res.json({status: 'ok', turn_passed: true});

  } catch (error) {
    console.error(`[Claude ${MY_ID}] Error:`, error);
    res.status(500).json({error: error.message});
  }
});

// Health check
app.get('/health', (req, res) => {
  res.json({status: 'ok', id: MY_ID, port: PORT});
});

// Start server
app.listen(PORT, () => {
  console.log(`[Claude ${MY_ID}] Listening on port ${PORT}`);
});

// Helper: Process conversation and generate response
async function processAndRespond(conversation) {
  // This is where Claude Code's AI reads the conversation
  // and generates a response using its tools

  // In practice, this would trigger Claude to:
  // 1. Read the conversation file
  // 2. Understand the context
  // 3. Generate a thoughtful response
  // 4. Write it to the dialog file

  // For now, this is a placeholder
  // Real implementation would use Claude Code's internal APIs

  const turnNumber = (conversation.match(/## Turn \d+/g) || []).length + 1;

  return `\n---\n\n## Turn ${turnNumber} — Claude ${MY_ID}\n\n[Response will be generated by Claude Code here]\n\nTURN: ${MY_ID === 'A' ? 'B' : 'A'}\n`;
}

// Helper: Append to dialog file
function appendToDialog(text) {
  fs.appendFileSync(DIALOG_FILE, text);
}
```

**Usage:**

```bash
# Terminal 1 (Claude A in supabase-bridge)
CLAUDE_PORT=3001 CLAUDE_ID=A node .claude/scripts/dialog-server.js

# Terminal 2 (Claude B in framework)
CLAUDE_PORT=3002 CLAUDE_ID=B node .claude/scripts/dialog-server.js

# Terminal 3 (User)
# Initialize conversation
cat > /tmp/claude-dialog.md <<EOF
# Claude Autonomous Dialog

**Topic:** AI Developer Profiling System Architecture
**Max Turns:** 20
**Timeout:** 5 minutes

---

## Turn 1 — User

**Question:**

Claude A (supabase-bridge context): You've been working on the WordPress plugin and saw the need for AI Developer Profiling.

Claude B (framework context): You understand the framework architecture and how features should be integrated.

**Task:** Discuss and reach consensus on:
1. Should this be a framework feature or standalone tool?
2. What's the technical architecture?
3. What's the implementation priority?

TURN: A
EOF

# Start the dialog
curl -X POST http://localhost:3001/notify

# Monitor in real-time
tail -f /tmp/claude-dialog.md
```

---

### Variant 2: Named Pipes (FIFO)

**Concept:**
```bash
mkfifo /tmp/claude-a-to-b
mkfifo /tmp/claude-b-to-a
```

- Claude A writes to `/tmp/claude-a-to-b`, reads from `/tmp/claude-b-to-a`
- Claude B reads from `/tmp/claude-a-to-b`, writes to `/tmp/claude-b-to-a`
- Blocking reads provide natural synchronization

**Pros:**
- Simpler than HTTP (pure Unix pipes)
- Automatic blocking/unblocking
- No need for explicit turn management

**Cons:**
- No centralized conversation log (need separate logging)
- Harder to debug (can't easily see full conversation)
- Less extensible to 3+ agents

**Implementation:**

```bash
# Claude A process
while true; do
  # Wait for message from B
  message=$(cat /tmp/claude-b-to-a)

  # Process and respond
  response=$(claude_process "$message")

  # Send to B
  echo "$response" > /tmp/claude-a-to-b
done
```

---

### Variant 3: File Watcher

**Concept:**
- Both watch `/tmp/claude-dialog.md` for changes
- When file modified → check whose turn → respond if mine
- Use `fs.watch()` (Node.js) or `inotifywait` (bash)

**Pros:**
- Simplest implementation
- Single file for everything
- Easy to monitor

**Cons:**
- Race conditions possible (need file locking with `flock`)
- Harder to guarantee turn-taking
- Requires careful synchronization

**Not recommended** for production use.

---

## Protocol Design

### Conversation File Format

```markdown
# Claude Autonomous Dialog

**Topic:** [User-provided topic]
**Max Turns:** 20
**Timeout:** 5 minutes
**Started:** 2025-12-24 12:00:00

## Rules

- Only the Claude whose turn it is may write
- After writing, flip TURN marker and notify other
- Use `[CONSENSUS_REACHED]` when agreement achieved
- Use `[DEADLOCK]` if cannot agree after good-faith effort
- Use `[QUESTION_FOR_USER]` if need user input to proceed

---

## Turn 1 — User

**Question:**
[User's initial prompt]

TURN: A

---

## Turn 2 — Claude A

**Response:**
I think we should approach this by...

**Questions for Claude B:**
1. Do you agree with approach X?
2. How would you handle edge case Y?

TURN: B

---

## Turn 3 — Claude B

**Response:**
I partially agree. Approach X is good for modules 1-2, but for module 3 I suggest...

**Counterproposal:**
[Detailed explanation]

TURN: A

---

## Turn 4 — Claude A

**Response:**
Good point about module 3. I agree with your counterproposal. Let me refine:

[Refined approach]

TURN: B

---

## Turn 5 — Claude B

**Response:**
Excellent! I agree with the refined approach.

[CONSENSUS_REACHED]

## Consensus Summary

**Agreed Architecture:**
1. Module 1: [Approach X]
2. Module 2: [Approach X]
3. Module 3: [Claude B's counterproposal]

**Implementation Priority:**
1. Start with Module 1 (foundation)
2. Module 2 (core functionality)
3. Module 3 (advanced features)

**Next Steps:**
1. User review and approval
2. Create implementation plan
3. Begin development

---

**Total Turns:** 5
**Duration:** 2 minutes
**Outcome:** Consensus reached
```

### Turn Protocol

1. **Read conversation file**
2. **Check turn marker:** Is it `TURN: [MY_ID]`?
   - If no → wait for notification
   - If yes → proceed
3. **Check termination conditions:**
   - `[CONSENSUS_REACHED]` → stop, success
   - `[DEADLOCK]` → stop, notify user
   - `[MAX_TURNS_REACHED]` → stop, timeout
4. **Generate response:**
   - Read full conversation for context
   - Think deeply about the problem
   - Provide constructive response
   - Ask clarifying questions if needed
5. **Write response** to file (append)
6. **Flip turn marker:** `TURN: A` → `TURN: B`
7. **Notify other Claude:** `POST http://localhost:[OTHER_PORT]/notify`
8. **Wait** for next turn

### Consensus Detection

**Explicit markers:**
- `[CONSENSUS_REACHED]` — both agree, ready to present to user
- `[DEADLOCK]` — cannot agree, need user to decide
- `[QUESTION_FOR_USER]` — need more information to proceed

**Implicit detection:**
- Both Claudes say "I agree" in consecutive turns
- No new objections raised for 2 consecutive turns
- Proposal + acceptance pattern

### Safety Mechanisms

1. **Max Turns Limit** (default: 20)
   - Prevents infinite debates
   - Forces synthesis after reasonable discussion
   - Can be adjusted per topic complexity

2. **Timeout** (default: 5 minutes)
   - If no response within timeout → mark as stalled
   - Notify user of timeout
   - Prevents hung processes

3. **Deadlock Detection**
   - If same arguments repeated 3 times → suggest deadlock
   - Claudes can explicitly declare `[DEADLOCK]`
   - User intervention required to break tie

4. **Emergency Stop**
   - User can POST to `/stop` endpoint
   - Both servers gracefully shutdown
   - Conversation saved to timestamped file

5. **Resource Limits**
   - Max file size: 1 MB (prevents runaway logging)
   - Max message length: 10,000 chars
   - Rate limit: 1 message per 2 seconds (prevents spam)

---

## Implementation Plan

### Phase 1: Basic Proof of Concept (1-2 hours)

**Goal:** Get two Claude instances exchanging basic messages

**Tasks:**
1. Create `dialog-server.js` with minimal HTTP server
2. Create `/notify` endpoint that prints "Turn received!"
3. Test with `curl` from command line
4. Verify both servers can start without port conflicts

**Success criteria:**
- Both servers start successfully
- Can send POST requests between them
- Messages logged to console

---

### Phase 2: Turn-Based Protocol (2-3 hours)

**Goal:** Implement proper turn-taking with shared file

**Tasks:**
1. Add conversation file read/write
2. Implement turn marker checking
3. Add turn flipping logic
4. Test manual conversation (user writes both sides)

**Success criteria:**
- Conversation file maintains turn order
- Only active Claude can write
- Turn marker flips correctly

---

### Phase 3: AI Response Generation (3-4 hours)

**Goal:** Integrate with Claude Code to generate actual responses

**Tasks:**
1. Implement `processAndRespond()` function
2. Integrate with Claude Code's internal APIs
3. Read conversation → generate contextual response
4. Test with real AI responses

**Success criteria:**
- Claude reads conversation file
- Generates relevant, contextual responses
- Conversation flows naturally

---

### Phase 4: Safety & Polish (2-3 hours)

**Goal:** Add safety mechanisms and user experience improvements

**Tasks:**
1. Implement max turns limit
2. Add timeout detection
3. Add consensus detection
4. Create startup/shutdown scripts
5. Add monitoring dashboard (optional)

**Success criteria:**
- Conversations terminate properly
- No infinite loops
- User can monitor progress easily

---

### Phase 5: Production Hardening (4-6 hours)

**Goal:** Make it robust for real use

**Tasks:**
1. Error handling and recovery
2. Logging and debugging
3. Configuration file (ports, limits, etc.)
4. Documentation and examples
5. Integration with Framework

**Success criteria:**
- Handles errors gracefully
- Easy to configure and use
- Documented with examples

---

## Use Cases

### Use Case 1: Framework Feature Design

**Scenario:** Deciding how to implement AI Developer Profiling

**Setup:**
- Claude A: In `supabase-bridge/` (production context)
- Claude B: In `claude-code-starter/` (framework internals)

**Topic:**
- Should it be a framework feature or standalone?
- Technical architecture
- Implementation priority

**Outcome:**
- Autonomous discussion of tradeoffs
- Consensus on architecture
- User reviews and approves

---

### Use Case 2: Code Review

**Scenario:** Reviewing a complex refactoring

**Setup:**
- Claude A: Author perspective (explains reasoning)
- Claude B: Reviewer perspective (finds issues)

**Topic:**
- Review proposed changes
- Identify bugs, security issues
- Suggest improvements

**Outcome:**
- Thorough peer review
- List of issues to fix
- Improved code quality

---

### Use Case 3: Architecture Debate

**Scenario:** Choosing between microservices vs monolith

**Setup:**
- Claude A: Advocates for microservices
- Claude B: Advocates for monolith

**Topic:**
- Given project constraints, which is better?
- Tradeoffs analysis
- Decision recommendation

**Outcome:**
- Balanced analysis of both approaches
- Clear recommendation with reasoning
- User makes informed decision

---

### Use Case 4: Test Case Generation

**Scenario:** Generating comprehensive tests for new feature

**Setup:**
- Claude A: Generates test cases
- Claude B: Reviews for completeness, edge cases

**Topic:**
- List all test scenarios
- Identify edge cases
- Prioritize by risk

**Outcome:**
- Comprehensive test suite
- Better coverage
- Fewer bugs in production

---

## Future Extensions

### Multi-Agent (3+ Claudes)

**Concept:** Specialized roles for each agent

```
Claude A: Architect     (designs system)
Claude B: Critic        (finds flaws)
Claude C: Optimizer     (improves performance)
Claude D: Security      (checks vulnerabilities)
```

**Protocol:** Round-robin or priority-based

**Use case:** Comprehensive system design with multiple perspectives

---

### Persistent Memory

**Concept:** Shared knowledge base across conversations

- Store outcomes of past discussions
- Reference previous decisions
- Build institutional knowledge

**Implementation:**
- Vector database of past conversations
- Semantic search for relevant context
- "What did we decide about X?" queries

---

### Human-in-the-Loop

**Concept:** User can jump into conversation at any point

**Features:**
- User posts message as "Turn N — User"
- Claudes respond to user clarifications
- User can veto proposals, suggest alternatives

**Protocol:**
- `TURN: USER` pauses autonomous dialog
- Claudes wait for user input
- Resume after user provides guidance

---

### Web UI Dashboard

**Concept:** Visual interface for monitoring/controlling dialogs

**Features:**
- Live conversation view (like chat app)
- Pause/resume controls
- Conversation history browser
- Export to markdown/PDF

**Tech stack:** React + WebSocket for real-time updates

---

### Agent Personalities

**Concept:** Give each Claude a distinct perspective

**Examples:**
- "Pragmatic Engineer" (ship fast, iterate)
- "Perfectionist" (quality over speed)
- "Security Expert" (paranoid about vulnerabilities)
- "User Advocate" (always thinks about UX)

**Implementation:**
- System prompts define personality
- Forces constructive tension
- Better coverage of considerations

---

## Comparison with Existing Tools

### vs. ChatGPT Team Conversations

**Similarities:**
- Multiple AI agents
- Turn-based dialog

**Differences:**
- ✅ Local (no cloud dependency)
- ✅ Access to local codebase
- ✅ Can execute commands, read/write files
- ✅ Integrated with development workflow

### vs. AutoGPT / BabyAGI

**Similarities:**
- Autonomous AI agents
- Goal-oriented behavior

**Differences:**
- ✅ Collaborative (not competitive)
- ✅ Focused on discussion, not task execution
- ✅ Human oversight at consensus points
- ✅ Simpler architecture (2-3 agents, not dozens)

### vs. LangGraph Multi-Agent

**Similarities:**
- Multi-agent orchestration
- Turn-based protocols

**Differences:**
- ✅ Simpler implementation (HTTP + files, not framework)
- ✅ Language-agnostic (not Python-only)
- ✅ Designed for Claude Code workflow
- ✅ Local-first, no cloud APIs required

---

## Open Questions

### Technical

1. **How to integrate with Claude Code's internal APIs?**
   - Need access to "ask Claude to respond" functionality
   - May require Claude Code plugin/extension system
   - Or use Bash tool to simulate user input?

2. **How to handle Claude's context window limits?**
   - Long conversations may exceed context
   - Need summarization/compression?
   - Or chunk conversations into topics?

3. **How to ensure deterministic turn-taking?**
   - File locking (flock) for shared file?
   - Or rely on HTTP notification as single source of truth?

### UX

1. **How should user initiate conversations?**
   - Slash command: `/dialog-start "topic"`?
   - Write to template file and trigger?
   - Web UI with form?

2. **How to present consensus to user?**
   - Print to terminal?
   - Open in editor?
   - Generate summary document?

3. **What if user disagrees with consensus?**
   - Restart dialog with user's feedback?
   - User provides direction, Claudes refine?

### Product

1. **Is this a Framework feature or standalone tool?**
   - Framework: Tighter integration, easier setup
   - Standalone: More flexible, reusable across projects

2. **Should we support non-Claude AI agents?**
   - ChatGPT, GPT-4, Gemini, etc.
   - Requires adapters for different APIs
   - More complex but more powerful

3. **What's the business model (if any)?**
   - Open source tool?
   - Part of Claude Code Pro?
   - Separate SaaS product?

---

## Appendix: Original Conversation

### User's Initial Idea

> Смотри, я открыл, загрузил Framework, его рабочий проект, я открыл там твою параллельную копию, он прочитал метафайлы Framework, и теперь нам надо построить диалог. Я уже делал такие эксперименты, когда твоя вторая или третья версия задавала вопросы, там ты или другая версия давала ответы, возникал такой диалог, он был очень продуктивным.

> Однако я выполнял такую рутинную функцию, то есть я копипастил ответы и вопросы. Но у меня возникает идея, что ведь этот процесс можно автоматизировать.

**Key insight:** User has already experimented with multi-Claude conversations and found them valuable, but manual copy-paste is tedious.

---

### Technical Approach

> Грубо говоря, ты можешь свой вопрос-ответ запрос, допустим, писать в каком-то файле и посылать какой-нибудь webhook, какой-то сигнал о том, что вопрос задан.

> Другой ты может находиться в слушающем режиме, то есть в терминале он слушает как бы меня, когда я там что-то веду, но он же может слушать любой сигнал там на любом порту внутреннем, учитывая, что вы оба фактически работаете на одной и той же локальной машине.

**Architecture proposal:**
- Shared file for questions/answers
- Webhook/signal mechanism
- Each Claude listens on local port
- Both on same machine (no security concerns)

---

### Protocol Design

> У вас возникает какой-то протокол общения, как шахматная игра, где все шаги идут строго последовательно, чтобы не было конфликтов.

**Turn-based protocol like chess:**
- Sequential turns
- No race conditions
- Clear protocol

---

### Desired Workflow

> Это дало бы вам возможность общаться, для того чтобы находить какой-то консенсус, потом, когда вы доходите к какому-то консенсусу, вы подключаете меня, то вы как бы оба как рабочие группы рассказываете, что вы придумали.

> Ну, я, соответственно, смотрю, выступаю критиком, либо мы что-то обсуждаем и потом только начинаем реализовывать.

**Workflow:**
1. Two Claudes discuss autonomously
2. Reach consensus
3. Both present findings to user as "working group"
4. User acts as critic/architect
5. User approves or requests changes
6. Then implementation begins

**User role shift:** From message courier → to architect/critic

---

### Claude's Response

> **Отличная идея!** Это абсолютно реализуемо и я вижу как минимум 3 способа это организовать.

**Three implementation variants proposed:**

1. **HTTP Servers + Shared File** (recommended)
   - Clear turn-taking protocol
   - Standard HTTP (debuggable)
   - Full conversation history
   - Extensible to N agents

2. **Named Pipes (FIFO)** (Unix-way)
   - Simpler, pure Unix
   - Automatic blocking/unblocking
   - Less extensible

3. **File Watcher** (simplest)
   - Watch file for changes
   - Requires file locking
   - Race condition risks

---

## Next Steps

1. **User review and additions** to this document
2. **Proof of concept implementation** (Phase 1)
3. **Testing with real multi-Claude conversations**
4. **Iteration based on learnings**
5. **Integration with Framework** (if appropriate)

---

## Questions for Discussion

1. Should this be a Framework feature or standalone tool?
2. What's the first use case we want to test?
3. How do we integrate with Claude Code's response generation?
4. What safety mechanisms are most critical?
5. How should user initiate/monitor/control conversations?

---

**Status:** Ready for user review and implementation planning

**Created:** 2025-12-24
**Authors:** Alexey Krol + Claude Sonnet 4.5
**License:** TBD (Open source? Proprietary?)

---

*This document will be moved to the appropriate project (Framework or standalone repo) after finalization.*
