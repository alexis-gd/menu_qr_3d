-- Fase 21: Notificaciones Push
-- Tabla para almacenar suscripciones push de dispositivos del admin
-- Idempotente: usa IF NOT EXISTS

CREATE TABLE IF NOT EXISTS push_subscriptions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    restaurante_id  INT NOT NULL,
    endpoint        VARCHAR(700) NOT NULL,
    subscription_data JSON NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_endpoint (endpoint(500)),
    INDEX idx_restaurante (restaurante_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
