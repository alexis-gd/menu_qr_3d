# docs/context-map.md — Mapa de contextos: menu_qr_3d

> Leer este archivo antes de actualizar cualquier contexto o crear reglas nuevas.

---

## Mapa de archivos

```
CLAUDE.md                              ← punto de entrada: stack, refs, LOG arquitectónico
│
├── docs/                              ← documentación del proyecto (en repo)
│   ├── context-map.md                 ← este archivo
│   ├── estado-producto.md             ← fases completadas, pendientes, API endpoints
│   ├── bd-schema.md                   ← CREATE TABLE, relaciones, índices
│   ├── bd-queries.md                  ← queries clave del sistema
│   ├── bd-reglas-negocio.md           ← reglas de negocio, PDO, mantenimiento
│   ├── bd-migraciones.md              ← historial de migraciones, seed, BD demo
│   ├── demos.md                       ← sistema de demos: arquitectura, trial, slug, create_demo.php
│   └── deploy.md                      ← cPanel: paths, FTP, migraciones, proxy Vite, entornos
│
├── .claude/                           ← reglas y comandos para Claude (en repo)
│   ├── rules/
│   │   ├── seguridad.md               ← config.php gitignored, prepared statements, HttpOnly
│   │   ├── convenciones-codigo.md     ← PHP/Vue/fechas/VueDatePicker/Pinia/componentes
│   │   ├── migraciones.md             ← obligatorio crear SQL antes de codificar el cambio
│   │   └── versionado.md             ← npm version patch/minor antes de cada commit
│   └── commands/
│       └── actualiza-contextos.md     ← flujo del comando "actualiza contextos"
│
└── MEMORY.md  (fuera del repo)        ← pitfalls, bugs resueltos, lecciones entre sesiones
```

---

## Fuente de verdad por archivo

| Archivo | Contenido | Actualizar cuando |
|---|---|---|
| `CLAUDE.md` | Stack, referencias, LOG arquitectónico | Cambia decisión arquitectónica permanente o se completa una tarea relevante |
| `docs/estado-producto.md` | Fases ✅/pendientes, API endpoints, estados de productos | Se completa una feature o cambia un flujo |
| `docs/bd-schema.md` | CREATE TABLE completos + relaciones + índices | Cambia el schema |
| `docs/bd-queries.md` | Queries del sistema | Cambia una query importante |
| `docs/bd-reglas-negocio.md` | Reglas de negocio, PDO, mantenimiento | Cambia una regla de negocio o cálculo |
| `docs/bd-migraciones.md` | Historial de migraciones, seed | Se agrega una migración |
| `docs/demos.md` | Sistema de demos (trial, slug, scripts, entornos) | Cambia el sistema de demos |
| `docs/deploy.md` | Deploy cPanel, entornos, FTP, proxy | Cambia el proceso de deploy o entornos |
| `.claude/rules/seguridad.md` | Reglas de seguridad permanentes | Se establece una regla nueva |
| `.claude/rules/convenciones-codigo.md` | Convenciones PHP/Vue permanentes | Se establece una convención nueva |
| `.claude/rules/migraciones.md` | Reglas de migraciones | Cambia el flujo de migraciones |
| `.claude/rules/versionado.md` | Semantic versioning | Cambia la política de versiones |
| `MEMORY.md` | Pitfalls, bugs sutiles, diferencias de entorno | Aparece un pitfall que no queremos repetir |

---

## Tabla de decisión: ¿qué actualizar según el tipo de cambio?

| Tipo de cambio | Archivo a actualizar |
|---|---|
| Se completa una feature / cambia un flujo del usuario | `docs/estado-producto.md` |
| Nueva tabla, columna, índice | `docs/bd-schema.md` + `docs/bd-migraciones.md` |
| Cambia una query clave | `docs/bd-queries.md` |
| Cambia una regla de negocio o cálculo | `docs/bd-reglas-negocio.md` |
| Se agrega una migración | `docs/bd-migraciones.md` |
| Cambia algo del sistema de demos (trial, slug, scripts) | `docs/demos.md` |
| Cambia el proceso de deploy, entornos, cPanel o proxy | `docs/deploy.md` |
| Se establece una regla de código permanente | `.claude/rules/` el archivo correspondiente |
| Decisión arquitectónica nueva o tarea arquitectónica completada | `CLAUDE.md` sección LOG |
| Pitfall técnico, bug sutil, diferencia de entorno | `MEMORY.md` |
| El usuario corrige el comportamiento de Claude | `MEMORY.md` |

Si un cambio toca varias capas → actualizar todos los que correspondan. Nunca forzar todo en uno.

---

## Prioridad si hay conflicto

- `CLAUDE.md` manda en reglas permanentes y LOG arquitectónico
- `docs/bd-schema.md` manda en schema y estructura de tablas
- `docs/estado-producto.md` manda en estado funcional actual
- `.claude/rules/` mandan en convenciones de código
- `MEMORY.md` complementa, no reemplaza

Si `MEMORY.md` contradice algo estable ya confirmado en otro archivo → corregir `MEMORY.md`.

---

## Meta de mantenimiento

Después de cambiar de proyecto y volver días después, el comando `actualiza contextos` debe ser suficiente para resincronizar todo. Claude sabe dónde mirar, qué actualizar y qué no mezclar.
