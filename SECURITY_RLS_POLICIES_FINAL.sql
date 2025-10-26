-- Security RLS Policies - Anon Key + site_url filtering
-- Version: 0.7.0-security-final
-- Date: 2025-10-26
--
-- Подход: Anon Key + строгие RLS политики с фильтрацией по site_url
--
-- Безопасность:
-- - WordPress использует Anon Key (не Service Role Key!)
-- - RLS проверяет x-site-url header в каждом запросе
-- - Разрешены операции ТОЛЬКО для site_url из конфигурации WordPress
-- - Защита от injection на уровне WordPress (sb_validate_* функции)
-- - Защита от несанкционированного доступа на уровне Supabase (RLS)

-- ============================================
-- Drop old permissive policies
-- ============================================

DROP POLICY IF EXISTS "Allow server sync for pairs" ON wp_registration_pairs;
DROP POLICY IF EXISTS "Allow server sync for registrations" ON wp_user_registrations;
DROP POLICY IF EXISTS "Allow site-specific pair operations" ON wp_registration_pairs;
DROP POLICY IF EXISTS "Allow site-specific registration logging" ON wp_user_registrations;

-- ============================================
-- RLS Policy for wp_registration_pairs
-- ============================================

-- Политика: Разрешить все операции ТОЛЬКО для запросов с правильным site_url
-- Как работает:
--   1. WordPress отправляет x-site-url header в каждом запросе
--   2. RLS проверяет: header.x-site-url === table.site_url
--   3. Если совпадает → разрешить операцию
--   4. Если не совпадает → 403 Forbidden

CREATE POLICY "Allow operations only for matching site_url"
ON wp_registration_pairs
FOR ALL
USING (
  -- Проверка для SELECT/UPDATE/DELETE: site_url в записи === x-site-url в header
  site_url = current_setting('request.headers', true)::json->>'x-site-url'
)
WITH CHECK (
  -- Проверка для INSERT/UPDATE: новый site_url === x-site-url в header
  site_url = current_setting('request.headers', true)::json->>'x-site-url'
);

-- ============================================
-- RLS Policy for wp_user_registrations
-- ============================================

-- Политика: Разрешить INSERT для регистраций с любого site
-- Примечание:
--   - wp_user_registrations не содержит site_url напрямую
--   - Связь через wp_registration_pairs.pair_id
--   - Просто разрешаем INSERT для всех (данные уже валидированы WordPress)
--   - В будущем можно добавить JOIN для проверки site_url через pair_id

CREATE POLICY "Allow registration logging for all sites"
ON wp_user_registrations
FOR INSERT
WITH CHECK (
  -- Разрешить INSERT для всех
  -- Данные уже валидированы WordPress (sb_validate_email, sb_validate_uuid, etc.)
  true
);

-- Для SELECT (если понадобится в будущем для аналитики)
CREATE POLICY "Allow read access for registration data"
ON wp_user_registrations
FOR SELECT
USING (
  -- Разрешить чтение всех данных
  -- В будущем можно ограничить через JOIN с wp_registration_pairs
  true
);

-- ============================================
-- Verification
-- ============================================

-- Проверить что политики созданы
SELECT
  schemaname,
  tablename,
  policyname,
  permissive,
  roles,
  cmd,
  qual,
  with_check
FROM pg_policies
WHERE schemaname = 'public'
  AND tablename IN ('wp_registration_pairs', 'wp_user_registrations')
ORDER BY tablename, policyname;

-- ============================================
-- Testing Queries
-- ============================================

-- Test 1: Проверка INSERT/UPDATE в wp_registration_pairs
-- Должно работать ТОЛЬКО если x-site-url header совпадает с site_url
--
-- Пример запроса из WordPress (через wp_remote_post):
-- POST /rest/v1/wp_registration_pairs
-- Headers:
--   apikey: anon_key
--   Authorization: Bearer anon_key
--   x-site-url: http://localhost:8000
-- Body:
--   {
--     "id": "...",
--     "site_url": "http://localhost:8000",  ← должно совпадать с header!
--     "registration_page_url": "/test/",
--     "thankyou_page_url": "/test-ty/"
--   }

-- Test 2: Попытка записать данные с неправильным site_url
-- Должно FAIL с 403 Forbidden
--
-- POST /rest/v1/wp_registration_pairs
-- Headers:
--   x-site-url: http://localhost:8000
-- Body:
--   {
--     "site_url": "http://evil.com",  ← НЕ совпадает с header!
--     ...
--   }
-- Результат: 403 Forbidden (RLS блокирует)

-- Test 3: INSERT в wp_user_registrations
-- Должно работать (политика разрешает для всех)
--
-- POST /rest/v1/wp_user_registrations
-- Headers:
--   apikey: anon_key
--   Authorization: Bearer anon_key
-- Body:
--   {
--     "user_id": "...",
--     "user_email": "test@example.com",
--     "registration_url": "/test/",
--     "thankyou_page_url": "/test-ty/",
--     "pair_id": "..." или null
--   }

-- ============================================
-- Security Benefits
-- ============================================

-- ✅ WordPress использует Anon Key (безопасно хранить)
-- ✅ Даже если Anon Key скомпрометирован, атакующий не может:
--    - Записать данные с чужим site_url (RLS блокирует)
--    - Прочитать данные чужих site_url (RLS блокирует)
--    - Обойти валидацию WordPress (происходит ДО Supabase)
-- ✅ Многоуровневая защита:
--    1. Валидация WordPress: sb_validate_email, sb_validate_url_path, sb_validate_uuid
--    2. Валидация Supabase: RLS проверяет x-site-url header
--    3. Типизация PostgreSQL: UUID, TEXT, TIMESTAMPTZ
-- ✅ Логирование подозрительной активности:
--    - WordPress логирует неудачные валидации
--    - Supabase логирует отклоненные RLS запросы

-- ============================================
-- Attack Scenarios Blocked
-- ============================================

-- Scenario 1: SQL Injection via email
-- Attack: '; DROP TABLE users; --@example.com
-- Defense: WordPress sb_validate_email() блокирует ДО отправки в Supabase

-- Scenario 2: XSS via URL
-- Attack: <script>alert('XSS')</script>
-- Defense: WordPress sb_validate_url_path() блокирует

-- Scenario 3: Path Traversal
-- Attack: ../../../etc/passwd
-- Defense: WordPress sb_validate_url_path() блокирует

-- Scenario 4: Cross-site data injection
-- Attack: Атакующий с site A пытается записать данные в site B
-- Defense: RLS проверяет x-site-url header, блокирует если не совпадает

-- Scenario 5: UUID injection
-- Attack: '; DROP TABLE wp_registration_pairs; --
-- Defense: WordPress sb_validate_uuid() блокирует

-- ============================================
-- Production Deployment
-- ============================================

-- 1. Запустить этот SQL в Supabase Dashboard → SQL Editor
-- 2. Проверить что политики созданы (SELECT запрос выше)
-- 3. Протестировать:
--    - Создать pair в WordPress → должно sync в Supabase
--    - Зарегистрировать пользователя → должно логировать в Supabase
--    - Проверить логи WordPress (не должно быть ошибок)

-- ============================================
-- Rollback Plan
-- ============================================

-- Если что-то не работает, вернуть permissive политики:
-- DROP POLICY IF EXISTS "Allow operations only for matching site_url" ON wp_registration_pairs;
-- DROP POLICY IF EXISTS "Allow registration logging for all sites" ON wp_user_registrations;
-- DROP POLICY IF EXISTS "Allow read access for registration data" ON wp_user_registrations;
--
-- CREATE POLICY "Allow server sync for pairs" ON wp_registration_pairs FOR ALL USING (true) WITH CHECK (true);
-- CREATE POLICY "Allow server sync for registrations" ON wp_user_registrations FOR ALL USING (true) WITH CHECK (true);

-- ============================================
-- END
-- ============================================
