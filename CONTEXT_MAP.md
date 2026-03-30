# SISTEMA DE CONTEXTO: menu_qr_3d
> Este archivo explica para que sirve cada contexto del proyecto, como se relacionan y cuando actualizar cada uno.
> Leer este mapa antes de actualizar contextos o crear reglas de documentacion.

---

## Objetivo

El proyecto usa varios archivos de contexto porque cada uno tiene una funcion distinta.
La idea es evitar mezclar:
- reglas permanentes
- estado actual del producto
- esquema de base de datos
- memoria operativa y pitfalls

Si todo se mezcla en un solo archivo, el contexto se vuelve pesado, repetido y dificil de mantener.

---

## Fuente de verdad por archivo

### `CLAUDE.md`
**Para que sirve:**
- reglas de trabajo del proyecto
- stack obligatorio
- decisiones arquitectonicas
- convenciones permanentes
- instrucciones operativas para Claude

**Si va aqui:**
- "siempre usar prepared statements"
- "este proyecto usa PHP nativo + Vue 3"
- "Dashboard.vue es la vista admin principal"
- decisiones tecnicas que seguiran siendo ciertas en futuras sesiones

**No va aqui:**
- listas largas de bugs menores
- detalles finos de una migracion puntual
- cambios temporales de una sesion

**Actualizar cuando:**
- cambie una regla de trabajo
- cambie una decision arquitectonica
- cambie una convencion permanente
- quieras ajustar el comportamiento esperado de Claude en este repo

---

### `CONTEXTO_PROYECTO.md`
**Para que sirve:**
- estado funcional actual del producto
- features implementadas
- comportamiento actual del sistema
- pendientes activos del producto
- flujo real de vistas, pantallas y modulos

**Si va aqui:**
- "Fase 20a implementada"
- "el menu publico ahora funciona sin ?r="
- "hay popup de tienda cerrada y modo lectura"
- pendientes funcionales o gaps de implementacion

**No va aqui:**
- reglas generales de trabajo del repo
- schema detallado de tablas y columnas
- bugs pequenos que solo son pitfall tecnico

**Actualizar cuando:**
- cambie el comportamiento del producto
- se implemente una feature
- cambie un flujo del usuario o del admin
- se cierre o abra un pendiente funcional

---

### `CONTEXTO_BASE_DE_DATOS.md`
**Para que sirve:**
- schema MySQL
- tablas, columnas, indices, relaciones
- consultas importantes
- reglas de negocio ligadas a datos

**Si va aqui:**
- nuevas columnas
- nuevas tablas
- cambios en ENUMs
- queries clave
- reglas de calculo que dependan de BD

**No va aqui:**
- decisiones de UI
- reglas de estilo o arquitectura frontend
- resumenes generales del producto

**Actualizar cuando:**
- haya migraciones nuevas
- cambie el schema
- cambien queries importantes
- cambie una regla de negocio que se resuelve desde BD o SQL/PHP

---

### `MEMORY.md`
**Para que sirve:**
- memoria operativa entre sesiones
- pitfalls reales
- bugs resueltos que conviene no repetir
- diferencias local / QA / prod
- detalles faciles de olvidar cuando trabajas en varios proyectos

**Si va aqui:**
- "toISOString() rompe fechas locales en Mexico"
- "VueDatePicker falla con v-if"
- "en Neubox no hay SSH, composer se corre local y se sube vendor/"
- "QA requiere correr cierta migracion antes del deploy"

**No va aqui:**
- historia completa del proyecto
- arquitectura base del sistema
- listas completas del schema

**Actualizar cuando:**
- aparezca un bug sutil que valga la pena recordar
- encuentres un pitfall tecnico recurrente
- descubras una diferencia importante entre entornos
- exista una leccion operativa que ayude en sesiones futuras

---

## Como se enlazan entre si

- `CLAUDE.md` define las reglas del juego.
- `CONTEXTO_PROYECTO.md` describe el estado actual del producto dentro de esas reglas.
- `CONTEXTO_BASE_DE_DATOS.md` describe la parte de datos que sostiene ese estado.
- `MEMORY.md` guarda lo facil de olvidar, lo que ya dolio y no queremos repetir.

En resumen:
- `CLAUDE.md` = como se trabaja
- `CONTEXTO_PROYECTO.md` = que existe hoy
- `CONTEXTO_BASE_DE_DATOS.md` = como esta modelado en datos
- `MEMORY.md` = que no debemos olvidar

---

## Regla practica para decidir que actualizar

Si cambias una regla, convencion o decision permanente:
- actualiza `CLAUDE.md`

Si cambias una feature, flujo o comportamiento del producto:
- actualiza `CONTEXTO_PROYECTO.md`

Si cambias tablas, columnas, queries o reglas ligadas a datos:
- actualiza `CONTEXTO_BASE_DE_DATOS.md`

Si descubres un bug raro, pitfall, workaround o diferencia de entorno:
- actualiza `MEMORY.md`

Si un cambio toca varias capas:
- actualiza todos los archivos que correspondan
- no fuerces toda la informacion en uno solo

---

## Comando operativo: "actualiza contextos"

Cuando el usuario diga `actualiza contextos`, el flujo correcto es:

1. Leer `CONTEXT_MAP.md`
2. Revisar los cambios recientes del proyecto
3. Decidir que contexto corresponde actualizar segun el tipo de cambio
4. Actualizar solo los archivos necesarios entre:
   - `CLAUDE.md`
   - `CONTEXTO_PROYECTO.md`
   - `CONTEXTO_BASE_DE_DATOS.md`
   - `MEMORY.md`
5. Mantener consistencia entre ellos y eliminar contradicciones obvias

No se debe actualizar un archivo por inercia si no hubo cambios de ese tipo.

---

## Prioridad si hay conflicto

Si dos archivos parecen contradecirse:
- `CLAUDE.md` manda en reglas permanentes y convenciones
- `CONTEXTO_BASE_DE_DATOS.md` manda en schema y estructura de datos
- `CONTEXTO_PROYECTO.md` manda en estado funcional actual
- `MEMORY.md` complementa, pero no reemplaza a los otros

Si `MEMORY.md` contradice una decision estable ya confirmada en otro archivo:
- corregir `MEMORY.md`

---

## Meta de mantenimiento

El objetivo no es documentar todo.
El objetivo es que, despues de cambiar de proyecto y volver dias despues, puedas decir:

`actualiza contextos`

y Claude sepa:
- donde mirar
- que actualizar
- que no mezclar
- como mantener alineados los contextos del repo
