-- fase23_qr_frase_wifi.sql
-- Agrega campos de personalización del QR impreso: frase y datos WiFi
-- Aplica en: local, demo, qa, prod

ALTER TABLE restaurantes
  ADD COLUMN IF NOT EXISTS qr_frase        VARCHAR(120)   NULL DEFAULT NULL       AFTER tema,
  ADD COLUMN IF NOT EXISTS qr_frase_activa TINYINT(1)     NOT NULL DEFAULT 1      AFTER qr_frase,
  ADD COLUMN IF NOT EXISTS qr_wifi_nombre  VARCHAR(100)   NULL DEFAULT NULL       AFTER qr_frase_activa,
  ADD COLUMN IF NOT EXISTS qr_wifi_clave   VARCHAR(100)   NULL DEFAULT NULL       AFTER qr_wifi_nombre,
  ADD COLUMN IF NOT EXISTS qr_wifi_activo  TINYINT(1)     NOT NULL DEFAULT 0      AFTER qr_wifi_clave;
