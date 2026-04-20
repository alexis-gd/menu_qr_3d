# .claude/commands/actualiza-contextos.md — Comando: actualiza contextos

Cuando el usuario diga `actualiza contextos`:

## Flujo

1. Leer `docs/context-map.md`
2. Revisar los cambios recientes del proyecto (git log, archivos modificados)
3. Decidir qué archivos tocar según la tabla del context-map
4. Actualizar **solo los necesarios** — no actualizar por inercia
5. Verificar que no haya contradicciones entre archivos

## Tabla de decisión rápida

| Tipo de cambio | Archivo |
|---|---|
| Nueva feature / flujo / comportamiento | `docs/estado-producto.md` |
| Tabla nueva, columna, query, regla de negocio BD | `docs/bd-schema.md` o `docs/bd-reglas-negocio.md` o `docs/bd-migraciones.md` |
| Sistema de demos (trial, slug, scripts, entornos) | `docs/demos.md` |
| Deploy, entornos, cPanel, proxy Vite | `docs/deploy.md` |
| Regla de seguridad, convención de código | `.claude/rules/seguridad.md` o `convenciones-codigo.md` |
| Regla de migraciones | `.claude/rules/migraciones.md` |
| Decisión arquitectónica permanente | `CLAUDE.md` sección LOG DE CAMBIOS |
| Pitfall técnico, bug sutil, diferencia de entorno | `MEMORY.md` (en `.claude/projects/.../memory/`) |
| El usuario corrige el comportamiento de Claude | `MEMORY.md` |

## Regla

No mezclar tipos de cambio en un solo archivo. Si el cambio toca varias capas → actualizar todos los que correspondan.
