# opencode setup guide — recreate this environment on a new machine

This document describes the actual opencode setup used on the current machine
and the steps to reproduce it elsewhere. It is written for Windows + PowerShell,
but the config files are cross-platform.

---

## Reality check

This session runs on the model `opencode/big-pickle`, served by opencode's own
provider. It does **not** run on Ollama.

Ollama is installed and configured, but only inside this Laravel project's
`opencode.json`, and it is **not selected**. Running `opencode models` from
another directory therefore does not list Ollama, while running it in this
project does.

The heavy work on this project was done by `big-pickle` (a hosted model), not by
the local 7B models. Switching to `qwen2.5-coder:7b` will give noticeably weaker
reasoning and tool calling.

---

## What is installed on the current machine

| Item | Current value |
|---|---|
| OS | Windows, PowerShell 5.1 |
| opencode CLI | `1.18.35`, installed globally via npm |
| npm prefix | `C:\nvm4w\nodejs` |
| Global config | `C:\Users\user\.config\opencode\opencode.jsonc` (only `$schema`) |
| Global package | `@opencode-ai/plugin@1.17.13` (version drift vs CLI) |
| Auth | Google only (`~\.local\share\opencode\auth.json`) |
| Active model | `opencode/big-pickle` |
| Ollama | Installed; `qwen2.5-coder:7b`, `qwen2:7b-instruct` |
| Ollama env | `OLLAMA_MODELS=E:\ollama\models`, `OLLAMA_CONTEXT_LENGTH=16384` |
| Project config | `C:\xampp\htdocs\arabcia-2026\opencode.json` |
| MCP | `laravel-boost` via `php artisan boost:mcp` |
| Project instructions | `C:\xampp\htdocs\arabcia-2026\AGENTS.md` (Laravel Boost generated) |
| Custom agents / commands / skills / plugins | None |
| Session data | `~\.local\share\opencode\opencode.db` — **2.3 GB** |

The functionality comes from four things, not from the model alone:

1. Core opencode tools and built-in agents.
2. Project `opencode.json` (provider + MCP).
3. `AGENTS.md` (project rules and Laravel Boost guidelines).
4. Laravel Boost MCP (`php artisan boost:mcp`) and PHP/Composer.

---

## New machine: step by step

### 1. Prerequisites

Install and put on `PATH`:

- Git
- Node.js LTS + npm
- PHP 8.4 + Composer (for the Laravel project and Boost MCP)
- Optional: Ollama, if you want local models

Verify:

```powershell
node -v
npm -v
php -v
composer -V
git --version
```

### 2. Install opencode

```powershell
npm install -g opencode-ai@latest
opencode --version
```

If PowerShell blocks scripts:

```powershell
Set-ExecutionPolicy -Scope CurrentUser RemoteSigned
```

### 3. Create the global config

Create `%USERPROFILE%\.config\opencode\opencode.jsonc`:

```jsonc
{
  "$schema": "https://opencode.ai/config.json",
  "model": "opencode/big-pickle",
  "autoupdate": true,
  "share": "manual",
  "tool_output": {
    "max_lines": 400,
    "max_bytes": 65536
  }
}
```

The `tool_output` block matters: without it, a single failing Laravel view test
can print thousands of lines of stack trace and bury the real error.

If you use local plugins, also create
`%USERPROFILE%\.config\opencode\package.json`:

```json
{
  "dependencies": {
    "@opencode-ai/plugin": "1.18.35"
  }
}
```

Then:

```powershell
npm install --prefix "$env:USERPROFILE\.config\opencode"
```

Match the plugin version to the CLI version. The old machine has `1.17.13`
against CLI `1.18.35`.

### 4. Authenticate

```powershell
opencode auth login
```

Choose the provider you actually want:

- `opencode` / Zen for `opencode/big-pickle`
- `google` for Gemini
- others as needed

List credentials:

```powershell
opencode auth list
```

`auth.json` lives under `~\.local\share\opencode\`. Do **not** commit it or copy
it into a project.

### 5. Install Ollama (optional)

```powershell
winget install Ollama.Ollama
ollama pull qwen2.5-coder:7b
ollama list
```

Set environment variables if you want the same layout:

```powershell
[Environment]::SetEnvironmentVariable("OLLAMA_MODELS", "E:\ollama\models", "User")
[Environment]::SetEnvironmentVariable("OLLAMA_CONTEXT_LENGTH", "16384", "User")
```

Make sure `ollama serve` is running and `http://localhost:11434/api/tags`
responds.

### 6. Project config

Inside the Laravel project root, create `opencode.json`:

```json
{
  "$schema": "https://opencode.ai/config.json",
  "model": "opencode/big-pickle",
  "provider": {
    "ollama": {
      "npm": "@ai-sdk/openai-compatible",
      "name": "Ollama (local)",
      "options": {
        "baseURL": "http://localhost:11434/v1"
      },
      "models": {
        "qwen2.5-coder:7b": {
          "name": "Qwen2.5 Coder 7B"
        },
        "qwen2:7b-instruct": {
          "name": "Qwen2 7B Instruct"
        }
      }
    }
  },
  "mcp": {
    "laravel-boost": {
      "type": "local",
      "enabled": true,
      "command": ["php", "artisan", "boost:mcp"]
    }
  }
}
```

Important: **the config only applies when opencode is started in that
directory.** Starting elsewhere will not load the Ollama provider or Boost MCP.

To actually use Ollama, either change `"model"` to
`"ollama/qwen2.5-coder:7b"` or run `/models` inside the TUI and pick it.

### 7. Install Laravel Boost

From the Laravel project root:

```powershell
composer require laravel/boost --dev
php artisan boost:install
```

This generates `AGENTS.md`, `.mcp.json`, and registers the MCP server. Confirm:

```powershell
Select-String composer.lock -Pattern 'laravel/boost'
Test-Path .mcp.json
```

The MCP command `php artisan boost:mcp` requires:

- the correct `php` on `PATH`
- the correct working directory (project root)
- a working Laravel app

### 8. Select the model and restart

Config is read once at startup. After any config change:

1. Quit opencode completely.
2. Restart it.
3. Check `/models`, or run:

```powershell
opencode models
```

You should see:

- `opencode/big-pickle`
- `ollama/qwen2.5-coder:7b` (only when run inside the project)

---

## What to copy vs recreate

| Copy | Recreate |
|---|---|
| `opencode.json` | `auth.json` via `opencode auth login` |
| `AGENTS.md` | `~\.local\share\opencode` session DB (fresh is fine) |
| `.mcp.json` | `node_modules` via `npm install` |
| `.ai/rules` if it exists | Ollama models via `ollama pull` |
| Project source | Global config (tiny, safer to recreate) |

Do **not** copy the 2.3 GB `opencode.db`. It is machine-specific session history
and is not required.

---

## Gotchas that will bite you

1. **Model IDs need a provider prefix.** `"model": "qwen2.5-coder:7b"` is
   invalid; use `"ollama/qwen2.5-coder:7b"`.
2. **`baseURL` must end in `/v1`** for the OpenAI-compatible provider.
3. If Ollama returns `401`, add a dummy key:

   ```json
   "options": { "baseURL": "http://localhost:11434/v1", "apiKey": "ollama" }
   ```

4. **Config is not hot-reloaded.** Restart after every change.
5. **Project config scope is the directory you launch from.** Launching in the
   wrong folder silently loses Ollama and Boost.
6. **PHP version mismatch exists on the old machine:** `AGENTS.md` says PHP 8.4,
   but tests ran with Laragon PHP `8.3.30`. Make sure the `php` used by
   `artisan` and the `php` used by the MCP server are the same.
7. **Laravel Boost regenerates `AGENTS.md`.** Do not hand-edit it if you plan to
   rerun `boost:install`.
8. **Single quotes for Tinker on PowerShell** to avoid variable expansion:

   ```powershell
   php artisan tinker --execute 'User::count();'
   ```

9. **PowerShell wrapper noise:** `opencode auth list` and `opencode models` may
   print a red PowerShell error while still working. It comes from the `.ps1`
   shim forwarding stderr. Use `cmd /c opencode models` if it disturbs scripts.
10. **Local 7B models are not equivalent.** They have limited context and
    unreliable tool calling. For real work on this codebase, stay on
    `opencode/big-pickle` or another hosted model.
11. **Never commit secrets.** Use `{env:VAR}` in MCP headers:

    ```json
    "headers": { "Authorization": "Bearer {env:GITHUB_TOKEN}" }
    ```

12. **Escape hatches if config breaks startup:**

    ```powershell
    $env:OPENCODE_DISABLE_PROJECT_CONFIG=1
    $env:OPENCODE_CONFIG="C:\path\to\opencode.json"
    $env:OPENCODE_PURE=1
    ```

---

## Verification checklist

```powershell
opencode --version
opencode auth list
opencode models
```

Then inside the project:

```powershell
opencode
```

Check:

- `/models` shows the model you expect
- the Boost MCP server is connected
- `php artisan --version` works from the same shell
- a simple prompt like "list the routes" uses the Boost tool rather than raw
  shell guesses

If all four are true, the new machine matches this one functionally.
