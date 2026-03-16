-- ============================================================
-- Migración Fase 8 — Iconos MDI para categorías
-- Fecha: 2026-03-15
-- Cambio: columna `emoji` VARCHAR(10) → `icono_nombre` VARCHAR(100)
--         Los valores pasan de unicode emoji a nombre de export MDI
--         Ej: '🍕' → 'mdiPizza'
-- ============================================================

-- 1. Renombrar columna (preserva datos existentes para el mapeo)
ALTER TABLE categorias
  CHANGE COLUMN emoji icono_nombre VARCHAR(100) DEFAULT NULL;

-- 2. Mapear emojis existentes a nombres MDI
UPDATE categorias SET icono_nombre = 'mdiPizza'            WHERE icono_nombre = '🍕';
UPDATE categorias SET icono_nombre = 'mdiHamburger'        WHERE icono_nombre = '🍔';
UPDATE categorias SET icono_nombre = 'mdiTaco'             WHERE icono_nombre = '🌮';
UPDATE categorias SET icono_nombre = 'mdiWrap'             WHERE icono_nombre = '🌯';
UPDATE categorias SET icono_nombre = 'mdiFoodVariant'      WHERE icono_nombre = '🥗';
UPDATE categorias SET icono_nombre = 'mdiBone'             WHERE icono_nombre = '🍖';
UPDATE categorias SET icono_nombre = 'mdiFoodSteak'        WHERE icono_nombre = '🥩';
UPDATE categorias SET icono_nombre = 'mdiFoodDrumstick'    WHERE icono_nombre = '🍗';
UPDATE categorias SET icono_nombre = 'mdiFoodVariant'      WHERE icono_nombre = '🍱';
UPDATE categorias SET icono_nombre = 'mdiNoodles'          WHERE icono_nombre = '🍜';
UPDATE categorias SET icono_nombre = 'mdiNoodles'          WHERE icono_nombre = '🍝';
UPDATE categorias SET icono_nombre = 'mdiPotSteam'         WHERE icono_nombre = '🍲';
UPDATE categorias SET icono_nombre = 'mdiPotSteam'         WHERE icono_nombre = '🥘';
UPDATE categorias SET icono_nombre = 'mdiFoodHotDog'       WHERE icono_nombre = '🌭';
UPDATE categorias SET icono_nombre = 'mdiFoodVariant'      WHERE icono_nombre = '🥙';
UPDATE categorias SET icono_nombre = 'mdiFoodVariant'      WHERE icono_nombre = '🍛';
-- Mariscos
UPDATE categorias SET icono_nombre = 'mdiSilverware'       WHERE icono_nombre = '🍣';
UPDATE categorias SET icono_nombre = 'mdiFoodForkDrink'    WHERE icono_nombre = '🦐';
UPDATE categorias SET icono_nombre = 'mdiFoodForkDrink'    WHERE icono_nombre = '🦞';
UPDATE categorias SET icono_nombre = 'mdiFoodForkDrink'    WHERE icono_nombre = '🦀';
UPDATE categorias SET icono_nombre = 'mdiFish'             WHERE icono_nombre = '🐟';
UPDATE categorias SET icono_nombre = 'mdiFoodForkDrink'    WHERE icono_nombre = '🍤';
UPDATE categorias SET icono_nombre = 'mdiFish'             WHERE icono_nombre = '🦑';
UPDATE categorias SET icono_nombre = 'mdiRice'             WHERE icono_nombre = '🍙';
UPDATE categorias SET icono_nombre = 'mdiFoodForkDrink'    WHERE icono_nombre = '🦪';
UPDATE categorias SET icono_nombre = 'mdiFish'             WHERE icono_nombre = '🐠';
-- Bebidas
UPDATE categorias SET icono_nombre = 'mdiCupWater'         WHERE icono_nombre = '🥤';
UPDATE categorias SET icono_nombre = 'mdiCoffee'           WHERE icono_nombre = '☕';
UPDATE categorias SET icono_nombre = 'mdiCupWater'         WHERE icono_nombre = '🧃';
UPDATE categorias SET icono_nombre = 'mdiTeaOutline'       WHERE icono_nombre = '🍵';
UPDATE categorias SET icono_nombre = 'mdiGlassMugVariant'  WHERE icono_nombre = '🍺';
UPDATE categorias SET icono_nombre = 'mdiGlassWine'        WHERE icono_nombre = '🍷';
UPDATE categorias SET icono_nombre = 'mdiGlassCocktail'    WHERE icono_nombre = '🍹';
UPDATE categorias SET icono_nombre = 'mdiCupOutline'       WHERE icono_nombre = '🥛';
UPDATE categorias SET icono_nombre = 'mdiGlassFlute'       WHERE icono_nombre = '🍸';
UPDATE categorias SET icono_nombre = 'mdiBottleWine'       WHERE icono_nombre = '🧉';
-- Postres
UPDATE categorias SET icono_nombre = 'mdiCakeVariant'      WHERE icono_nombre = '🍰';
UPDATE categorias SET icono_nombre = 'mdiCakeLayered'      WHERE icono_nombre = '🎂';
UPDATE categorias SET icono_nombre = 'mdiCakeVariant'      WHERE icono_nombre = '🍮';
UPDATE categorias SET icono_nombre = 'mdiIceCream'         WHERE icono_nombre = '🍦';
UPDATE categorias SET icono_nombre = 'mdiCupcake'          WHERE icono_nombre = '🧁';
UPDATE categorias SET icono_nombre = 'mdiCookieOutline'    WHERE icono_nombre = '🍩';
UPDATE categorias SET icono_nombre = 'mdiCookieOutline'    WHERE icono_nombre = '🍪';
UPDATE categorias SET icono_nombre = 'mdiCakeVariant'      WHERE icono_nombre = '🍫';
UPDATE categorias SET icono_nombre = 'mdiCandy'            WHERE icono_nombre = '🍬';
UPDATE categorias SET icono_nombre = 'mdiCandyOutline'     WHERE icono_nombre = '🍭';
UPDATE categorias SET icono_nombre = 'mdiChartPie'         WHERE icono_nombre = '🥧';
UPDATE categorias SET icono_nombre = 'mdiFoodVariant'      WHERE icono_nombre = '🍡';
-- Extras
UPDATE categorias SET icono_nombre = 'mdiStar'             WHERE icono_nombre = '⭐';
UPDATE categorias SET icono_nombre = 'mdiFireCircle'       WHERE icono_nombre = '🔥';
UPDATE categorias SET icono_nombre = 'mdiDiamond'          WHERE icono_nombre = '💎';
UPDATE categorias SET icono_nombre = 'mdiLeaf'             WHERE icono_nombre = '🌿';
UPDATE categorias SET icono_nombre = 'mdiMedal'            WHERE icono_nombre = '🥇';
UPDATE categorias SET icono_nombre = 'mdiHeart'            WHERE icono_nombre = '❤️';
UPDATE categorias SET icono_nombre = 'mdiChiliHot'         WHERE icono_nombre = '🌶️';
UPDATE categorias SET icono_nombre = 'mdiLeaf'             WHERE icono_nombre = '🥑';
UPDATE categorias SET icono_nombre = 'mdiAutoFix'          WHERE icono_nombre = '✨';
UPDATE categorias SET icono_nombre = 'mdiNewBox'           WHERE icono_nombre = '🆕';
UPDATE categorias SET icono_nombre = 'mdiTarget'           WHERE icono_nombre = '🎯';

-- 3. Fallback: cualquier emoji no mapeado → mdiFoodVariant
UPDATE categorias
  SET icono_nombre = 'mdiFoodVariant'
  WHERE icono_nombre IS NOT NULL
    AND icono_nombre NOT LIKE 'mdi%';
