# docs/bd-reglas-negocio.md — Reglas de negocio en BD

## productos

- `foto_principal` → relativo a `/uploads/`, ej: `fotos/1/foto_1_0_1234.jpg`. URL completa: `UPLOADS_URL . $foto_principal`.
- `modelo_glb_path` → solo nombre del archivo, ej: `modelo_1_1234.glb`. URL completa: `UPLOADS_URL . 'modelos/' . $modelo_glb_path`.
- `tiene_ar = 1` se setea al subir un `.glb` válido (endpoint `upload-glb`) o cuando el cron descarga exitosamente de Meshy.
- `stock`: NULL = sin control; 0 = agotado (overlay "No disponible"); > 0 = unidades. API descuenta con `GREATEST(0, stock - cantidad)`.
- `disponible`: 0 = "Próximamente" (visible sin botón de compra); 1 = normal. Distinto de `activo=0` (borrado lógico).
- Borrado siempre lógico (`activo = 0`). Nunca DELETE.

## restaurantes

- `stock_minimo_aviso`: umbral para badge "Últimas N piezas". 0 = badge desactivado.
- `tienda_abierta` es calculado en PHP (no almacenado): `false` si `tienda_cerrada_manual=1` O si hora actual está fuera del rango `tienda_horarios`. `true` si `tienda_horarios = NULL`.
- `trial_expires_at`: NULL = sin restricción (plantilla o cliente real), fecha pasada = trial vencido, fecha futura = trial activo.
- Logo: ruta relativa en BD (ej: `logos/logo_1_1234.jpg`). URL completa se antepone en ambos endpoints (`menu` y `restaurantes`).

## Sistema de recompensas (sellos)

- `clientes.total_compras` incrementa por cada pedido confirmado.
- `ciclos_completados = FLOOR(total_compras / compras_necesarias)`
- `tiene_recompensa = ciclos_completados > recompensas_ganadas`
- Al aplicar recompensa: `recompensas_ganadas += 1`. NO incrementa `total_compras` ni `contada_en_recompensas`.
- **Cuidado:** cambiar `compras_necesarias` afecta a todos los clientes inmediatamente.
- Cancelación revierte `total_compras` solo si `contada_en_recompensas = 1`.

## Códigos de promotor

- Tipos: `descuento_porcentaje`, `descuento_fijo`, `envio_gratis`.
- `usos_maximo NULL` = sin límite. `telefono_restringido` = solo ese número puede canjearlo.
- `envio_gratis`: al aplicarse, `costo_envio` queda en 0. `descuento_promo` registra el monto perdonado. NO se aplica si cliente elige "recoger" ni si ya hay envío gratis por umbral de monto.
- Recompensa y cupón son mutuamente excluyentes — recompensa tiene prioridad.
- Cancelación decrementa `codigos_promo.usos` si `codigo_promo IS NOT NULL`.

## Descuentos en pedidos

- `total = subtotal + costo_envio - descuento_recompensa - descuento_promo`. Nunca se reescribe.
- `ajuste_manual` (Fase 19): aditivo al `total` original. Positivo = cargo extra, negativo = descuento post-venta.
- `total_final = total + ajuste_manual` se computa en SELECT, no almacenado. No afecta recompensas.

## Pedidos programados (Fase 20a)

- `fecha_programada DATE` y `hora_programada TIME`: NULL si pedido normal.
- `pedidos_programar_activo` en restaurantes controla si aparece el botón en popup de tienda cerrada.
- TabPedidos muestra borde azul + chip "📅 Prog." cuando `fecha_programada IS NOT NULL`.

## Folio no secuencial (Fase 19a)

- Formato `YYYYMMDD-KBR4` (fecha + 3 consonantes sin I/O/U + 1 dígito).
- Generado con `random_int()` PHP en loop de 5 intentos con verificación de unicidad.
- Índice UNIQUE en `(restaurante_id, numero_pedido)`.

## Push notifications (Fase 21)

- Una fila por endpoint de navegador/dispositivo.
- `endpoint` es único: re-suscripción usa `ON DUPLICATE KEY UPDATE`.
- Si `Minishlink/WebPush` reporta endpoint inválido/expirado, el backend lo elimina.
- El envío de push al crear un pedido debe fallar **silenciosamente** — nunca impedir que el pedido se guarde.

## mesas

- `mesas.numero` es VARCHAR: permite "1", "VIP", "Terraza-2". Único por restaurante.
- URL del QR por mesa: `{BASE_URL}/menu/?r={restaurante.slug}&mesa={mesa.numero}`

## Meshy jobs

- Un producto puede tener múltiples intentos en `meshy_jobs`.
- El cron solo procesa `status IN ('pending', 'processing') AND intentos < 30`.
- Si Meshy falla > 30 veces, el job queda en `processing` excluido del cron. El admin puede ver `error_msg` y reintentar subiendo nuevas fotos.

---

## Conexión PDO en PHP

```php
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4',
            config('db.host'), config('db.name'));
        $pdo = new PDO($dsn, config('db.user'), config('db.pass'), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
```

Siempre **prepared statements** con parámetros nombrados (`:param`). Nunca interpolar en SQL.

---

## Notas de mantenimiento

- Limpiar sesiones vencidas: `DELETE FROM sesiones_admin WHERE expira_en < NOW();`
- Archivos `.glb` pesan 3-10 MB. Monitorear espacio en disco del cPanel.
- Regenerar modelo 3D: poner `tiene_ar = 0`, `modelo_glb_path = NULL`, crear nuevo registro en `meshy_jobs`.
- Reporte de cupones envío gratis: pedidos con `codigo_promo IS NOT NULL` y `descuento_promo = 0` (cupón sin monto descontado = fue tipo `envio_gratis`).
