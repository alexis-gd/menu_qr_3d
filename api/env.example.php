<?php
/**
 * Selector de entorno — env.example.php
 *
 * INSTRUCCIONES:
 *   1. Copia este archivo: env.example.php → env.php
 *   2. Cambia el valor de APP_ENV según el servidor
 *   3. env.php está en .gitignore — NUNCA subir al repositorio
 *
 * Valores válidos: local | qa | prod
 *
 * Cada servidor tiene su propio env.php:
 *   - Máquina de desarrollo  → define('APP_ENV', 'local');
 *   - Servidor QA            → define('APP_ENV', 'qa');
 *   - Servidor producción    → define('APP_ENV', 'prod');
 *
 * Alternativa sin archivo: setear la variable de entorno en .htaccess del servidor:
 *   SetEnv APP_ENV qa
 */

define('APP_ENV', 'local');   // ← cambiar según el servidor
