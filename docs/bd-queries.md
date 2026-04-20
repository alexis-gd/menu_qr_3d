# docs/bd-queries.md — Consultas clave del sistema

## Menú público completo (más usada)

```sql
SELECT
  r.nombre AS restaurante_nombre, r.logo_url, r.color_primario,
  c.id AS cat_id, c.nombre AS cat_nombre, c.icono AS cat_icono, c.orden AS cat_orden,
  p.id AS prod_id, p.nombre AS prod_nombre, p.descripcion, p.precio,
  p.foto_principal, p.modelo_glb_path, p.tiene_ar, p.es_destacado, p.disponible,
  p.stock, p.tiene_personalizacion
FROM restaurantes r
JOIN categorias c ON c.restaurante_id = r.id AND c.activo = 1
JOIN productos p  ON p.categoria_id = c.id AND p.activo = 1
WHERE r.slug = :slug AND r.activo = 1
ORDER BY c.orden ASC, p.orden ASC, p.nombre ASC;
-- API ya NO filtra AND p.disponible=1 — devuelve todos con activo=1
-- (disponible=0 es "Próximamente", control es 100% frontend)
```

Si no viene `slug` en el request → devuelve el primer restaurante activo (single-tenant prod).

## Mesas activas de un restaurante

```sql
SELECT m.id, m.numero, m.qr_generado,
       r.slug AS restaurante_slug, r.nombre AS restaurante_nombre
FROM mesas m
JOIN restaurantes r ON r.id = m.restaurante_id
WHERE m.restaurante_id = :rid AND m.activo = 1
ORDER BY CAST(m.numero AS UNSIGNED), m.numero;
-- Orden inteligente: 1,2,10 antes que "Terraza","VIP"
```

## Jobs pendientes para el cron Meshy

```sql
SELECT id, producto_id, meshy_task_id, intentos
FROM meshy_jobs
WHERE status IN ('pending', 'processing') AND intentos < 30
ORDER BY created_at ASC;
```

## Reporte de ventas por rango de fechas

```sql
SELECT
  SUM(total + ajuste_manual) AS ingresos_netos,
  SUM(costo_envio)           AS ingresos_envio,
  SUM(descuento_recompensa)  AS descuentos_recompensa,
  SUM(descuento_promo)       AS descuentos_promo,
  SUM(ajuste_manual)         AS ajustes_manuales,
  COUNT(*)                   AS total_pedidos
FROM pedidos
WHERE restaurante_id = :rid
  AND status != 'cancelado'
  AND DATE(created_at) BETWEEN :desde AND :hasta;
```

## Historial cliente por teléfono

```sql
SELECT total_compras, recompensas_ganadas
FROM clientes
WHERE restaurante_id = :rid AND telefono = :tel;
-- ciclos_completados = FLOOR(total_compras / compras_necesarias)
-- tiene_recompensa   = ciclos_completados > recompensas_ganadas
```

## Grupos y opciones de un producto

```sql
SELECT g.id, g.nombre, g.tipo, g.obligatorio,
       g.min_selecciones, g.max_selecciones, g.max_dinamico_grupo_id, g.orden,
       o.id AS op_id, o.nombre AS op_nombre, o.precio_extra, o.max_override, o.orden AS op_orden
FROM producto_grupos g
LEFT JOIN producto_opciones o ON o.grupo_id = g.id AND o.activo = 1
WHERE g.producto_id = :pid AND g.activo = 1
ORDER BY g.orden, o.orden;
```

## Simular trial vencido / restaurar (pruebas)

```sql
-- Vencer
UPDATE restaurantes SET trial_expires_at = '2020-01-01' WHERE slug = 'tacos-prueba';
-- Restaurar 7 días
UPDATE restaurantes SET trial_expires_at = DATE_ADD(NOW(), INTERVAL 7 DAY) WHERE slug = 'tacos-prueba';
-- Sin restricción (plantilla o cliente real)
UPDATE restaurantes SET trial_expires_at = NULL WHERE slug = 'tacos-prueba';
```
