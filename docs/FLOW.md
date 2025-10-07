Окей, тогда вот отдельный .md-фрагмент, который можно держать рядом (например FLOW.md или вставлять в документацию как визуализацию).

# 🔄 Поток аутентификации Supabase ↔ WordPress

```mermaid
sequenceDiagram
    participant User as Пользователь (браузер)
    participant Supabase as Supabase Auth
    participant Callback as WP Callback-страница
    participant Plugin as WP Supabase Bridge (плагин)
    participant WP as WordPress Core

    User->>Supabase: Нажимает "Войти через Google"<br/>signInWithOAuth()
    Supabase-->>User: Открывает Google OAuth диалог
    User->>Supabase: Подтверждает вход (Google)
    Supabase-->>Callback: Редирект на /supabase-callback/<br/>с токеном (session)

    Callback->>Callback: JS вызывает supabase.auth.getSession()
    Callback->>Plugin: POST /wp-json/supabase-auth/callback<br/>с access_token (JWT)

    Plugin->>Supabase: Запрашивает JWKS (.well-known/jwks.json)
    Plugin->>Plugin: Проверяет подпись JWT и клеймы
    Plugin->>WP: Создаёт/находит WP-пользователя<br/>wp_create_user / get_user_by
    Plugin->>WP: Ставит auth cookie<br/>wp_set_auth_cookie()

    WP-->>User: Пользователь залогинен в WordPress

📌 Этот flow показывает:
	•	весь клиентский цикл (кнопка → Supabase → callback-страница),
	•	серверную проверку токена плагином,
	•	и финальный логин в WordPress.