# Framework Bug Report - Completion Protocol Errors

**Date:** 2025-12-18
**Framework Version:** Claude Code Starter v2.3.1
**Project:** supabase-bridge (host project)
**Session Duration:** ~3 hours

---

## Bug #1: Dialog Export Creates Incomplete Files

### Expected Behavior:
- 3-hour conversation session should create ~200KB dialog file
- Should export full conversation history

### Actual Behavior:
- Exported 15 sessions, all ~5KB each
- Current session synced (+1081 messages) but file size doesn't match
- Content appears incomplete/wrong

### Steps to Reproduce:
1. Run long conversation session (3+ hours)
2. Execute: `node .claude/dist/claude-export/cli.js export`
3. Check file sizes in `dialog/` directory

### Evidence:
```
Exported 15 new sessions to /Users/alexeykrolmini/Downloads/Code/supabase-bridge/dialog
Syncing current active session...
Updated current session: +1081 message(s)
```

User report: "по диалогам там бред, то есть совершенно не то" (dialogs contain nonsense)

### Impact:
- Critical: Conversation history not preserved correctly
- Protocol Completion fails its primary purpose (preserve session data)

---

## Bug #2: Framework/Host Project Confusion

### Expected Behavior:
- CLAUDE.md should clearly indicate this IS a framework-managed project
- Completion Protocol should execute all steps without confusion
- Agent should not need to determine "is this a framework project?"

### Actual Behavior:
- Agent incorrectly concluded "это не framework проект" (this is not a framework project)
- Skipped critical steps (dialog export) based on wrong assumption
- Confusion between npm scripts vs direct node commands

### Root Cause Analysis:

**Protocol Says:**
```bash
npm run dialog:export --no-html
```

**Project Has:**
- ✅ `.claude/` directory with framework files
- ✅ `dist/claude-export/cli.js` working (proven by `/ui` command)
- ❌ No `package.json` with npm scripts
- ❌ No clear indicator: "use node commands, not npm"

**What Happened:**
1. Agent ran: `npm run dialog:export`
2. Got error: "Missing script: dialog:export"
3. Agent concluded: "не framework проект" → skipped export
4. **Correct action:** Run `node .claude/dist/claude-export/cli.js export`

### User Feedback:
> "framework, когда ты следуешь framework, ты должен делать все правильно"
> (when following framework, you should do everything correctly)

> "Ты не должен путаться: framework проект, не framework проект"
> (You shouldn't be confused: framework project or not)

### Proposed Solution:
1. CLAUDE.md should auto-detect project type and provide exact commands
2. OR: Include fallback detection in protocol:
   ```bash
   # Try npm first, fallback to direct node command
   npm run dialog:export --no-html 2>/dev/null || \
   node .claude/dist/claude-export/cli.js export
   ```
3. Add check in Cold Start: "Framework installation verified: ✅/❌"

---

## Bug #3: Completion Protocol Lacks Error Recovery

### Expected Behavior:
- If a step fails, protocol should provide clear next action
- Should auto-detect correct command format for current project

### Actual Behavior:
- Agent skips failed steps silently
- No retry logic
- No diagnostic output

### Example:
```
Step 3: Export dialogs
  → npm run dialog:export  [FAILED]
  → Agent: "нет npm скрипта, пропускаю"  [WRONG]
  → Should: Try alternative command automatically
```

### Impact:
- Medium: User has to manually catch and fix protocol errors
- Protocol doesn't self-heal

---

## Bug #4: Missing Completion Protocol Logging

### Expected Behavior:
- Completion Protocol Step 0: "Initialize Completion Logging"
- Should create log in `.claude/logs/completion/`
- Should track all protocol steps and errors

### Actual Behavior:
- Step 0 was NOT executed
- No completion log created
- Only realized error when user asked about logs

### Evidence:
```bash
# Cold Start created log:
.claude/logs/cold-start/supabase-bridge-20251217-001135.md  ✅

# Completion should create:
.claude/logs/completion/supabase-bridge-YYYYMMDD-HHMMSS.md  ❌
```

### Root Cause:
- Agent skipped Step 0 (no clear reminder/enforcement)
- Protocol execution not strictly enforced

---

## Severity Assessment

| Bug | Severity | Impact |
|-----|----------|--------|
| #1: Incomplete Dialog Export | 🔴 Critical | Data loss, history not preserved |
| #2: Framework Confusion | 🟡 High | Protocol steps skipped, wrong assumptions |
| #3: No Error Recovery | 🟡 Medium | Manual intervention required |
| #4: Missing Completion Logs | 🟡 Medium | No audit trail of protocol execution |

---

## Recommended Actions for Framework Team

1. **Immediate:**
   - Fix dialog export file size/completeness issue
   - Add project type auto-detection to CLAUDE.md

2. **Short-term:**
   - Implement fallback commands (npm → node)
   - Add strict step enforcement (can't skip required steps)
   - Auto-create protocol logs (mandatory, not optional)

3. **Long-term:**
   - Protocol execution validator
   - Self-healing/retry logic
   - Better error messages in protocol steps

---

## Session Context

**What We Were Doing:**
- Debugging browser-specific Magic Link hash detection issue
- Set up SSH access for production
- Created test environment
- Isolated problem to specific page

**Why Protocol Matters:**
- Need full dialog history to understand debugging progress
- User will continue work tomorrow
- Context preservation critical for multi-day debugging

---

**Generated:** 2025-12-18T22:20:00+03:00
**Reporter:** Claude Sonnet 4.5 (session agent)
**Framework:** Claude Code Starter v2.3.1
**Target:** Framework development team for analysis
