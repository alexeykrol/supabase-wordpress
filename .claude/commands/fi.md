---
description: Sprint/Phase completion protocol
---

# Completion Protocol

⚠️ **CRITICAL: Use specialized agent to ensure ALL steps are completed**

## Why Agent?

Long sessions cause context compactification (summarization). Even if you read CLAUDE.md at the start, you FORGOT details by now. Security scan step is CRITICAL and often missed.

**Solution:** Dedicated agent reads CLAUDE.md fresh and executes ALL steps without forgetting.

---

## Execute Protocol

**Use Task tool with general-purpose agent:**

```
subagent_type: 'general-purpose'
prompt: "Execute Completion Protocol from CLAUDE.md. Steps:

1. Read full Completion Protocol:
   grep -A 300 '## Completion Protocol' CLAUDE.md

2. Execute ALL steps from protocol, including:
   - Step 0.5: Re-read protocol (if needed)
   - Step 1: Build (if code changed)
   - Step 2: Update metafiles
   - Step 3: Export dialogs
   - Step 3.5: Security Scan (MANDATORY before commit!)
   - Step 4: Git commit
   - Step 5: Ask about push & PR
   - Step 6: Mark session clean

3. CRITICAL: Do NOT skip Step 3.5 (Security Scan)
   bash security/security-scan.sh
   If fails → fix credentials → re-scan → ONLY THEN commit

4. Return completion report with:
   - All steps executed
   - Security scan result
   - Commits created
   - Session status"
```

---

## Agent Guarantees

✅ Reads CLAUDE.md fresh (no summarization)
✅ Executes ALL steps (including security scan)
✅ Cannot "forget" steps
✅ Returns verifiable report
✅ Independent of main session context
