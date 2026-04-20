# .claude/rules/versionado.md — Semantic Versioning

El proyecto usa **Semantic Versioning** gestionado en `package.json`.
La versión se inyecta en el build vía `vite.config.js` → `__APP_VERSION__` y se muestra en el footer de `MenuPublico.vue`.

## Regla por tipo de commit

| Tipo | Comando | Resultado |
|---|---|---|
| `feat:` | `npm version minor` | `1.X.0` |
| `fix:` / `chore:` / `style:` / `refactor:` | `npm version patch` | `1.1.X` |
| Cambio disruptivo (raro) | `npm version major` | `X.0.0` |

## Flujo obligatorio

1. Terminar los cambios de código
2. Correr `npm version patch|minor|major` — edita `package.json` automáticamente
3. Hacer el commit con el mensaje convencional

Claude Code debe correr el `npm version` adecuado como parte de cada sesión de commit, **sin que el usuario tenga que pedirlo**.
