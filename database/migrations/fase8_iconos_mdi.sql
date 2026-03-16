-- ============================================================
-- Migración Fase 8 — Iconos MDI para categorías
-- Fecha: 2026-03-15
-- Cambio: columna `emoji` VARCHAR(10) → `icono` VARCHAR(100) (coincide con campo en API)
--         Los valores pasan de unicode emoji a nombre de export MDI
--         Ej: '🍕' → 'mdiPizza'
-- ============================================================

-- 1. Renombrar columna solo si aún se llama 'emoji' (en QA ya se llama 'icono', omitir)
-- ALTER TABLE categorias CHANGE COLUMN emoji icono VARCHAR(100) DEFAULT NULL;

-- 2. Mapear emojis existentes a nombres MDI
UPDATE categorias SET icono = 'mdiPizza'            WHERE icono = '🍕';
UPDATE categorias SET icono = 'mdiHamburger'        WHERE icono = '🍔';
UPDATE categorias SET icono = 'mdiTaco'             WHERE icono = '🌮';
UPDATE categorias SET icono = 'mdiWrap'             WHERE icono = '🌯';
UPDATE categorias SET icono = 'mdiFoodVariant'      WHERE icono = '🥗';
UPDATE categorias SET icono = 'mdiBone'             WHERE icono = '🍖';
UPDATE categorias SET icono = 'mdiFoodSteak'        WHERE icono = '🥩';
UPDATE categorias SET icono = 'mdiFoodDrumstick'    WHERE icono = '🍗';
UPDATE categorias SET icono = 'mdiFoodVariant'      WHERE icono = '🍱';
UPDATE categorias SET icono = 'mdiNoodles'          WHERE icono = '🍜';
UPDATE categorias SET icono = 'mdiNoodles'          WHERE icono = '🍝';
UPDATE categorias SET icono = 'mdiPotSteam'         WHERE icono = '🍲';
UPDATE categorias SET icono = 'mdiPotSteam'         WHERE icono = '🥘';
UPDATE categorias SET icono = 'mdiFoodHotDog'       WHERE icono = '🌭';
UPDATE categorias SET icono = 'mdiFoodVariant'      WHERE icono = '🥙';
UPDATE categorias SET icono = 'mdiFoodVariant'      WHERE icono = '🍛';
-- Mariscos
UPDATE categorias SET icono = 'mdiSilverware'       WHERE icono = '🍣';
UPDATE categorias SET icono = 'mdiFoodForkDrink'    WHERE icono = '🦐';
UPDATE categorias SET icono = 'mdiFoodForkDrink'    WHERE icono = '🦞';
UPDATE categorias SET icono = 'mdiFoodForkDrink'    WHERE icono = '🦀';
UPDATE categorias SET icono = 'mdiFish'             WHERE icono = '🐟';
UPDATE categorias SET icono = 'mdiFoodForkDrink'    WHERE icono = '🍤';
UPDATE categorias SET icono = 'mdiFish'             WHERE icono = '🦑';
UPDATE categorias SET icono = 'mdiRice'             WHERE icono = '🍙';
UPDATE categorias SET icono = 'mdiFoodForkDrink'    WHERE icono = '🦪';
UPDATE categorias SET icono = 'mdiFish'             WHERE icono = '🐠';
-- Bebidas
UPDATE categorias SET icono = 'mdiCupWater'         WHERE icono = '🥤';
UPDATE categorias SET icono = 'mdiCoffee'           WHERE icono = '☕';
UPDATE categorias SET icono = 'mdiCupWater'         WHERE icono = '🧃';
UPDATE categorias SET icono = 'mdiTeaOutline'       WHERE icono = '🍵';
UPDATE categorias SET icono = 'mdiGlassMugVariant'  WHERE icono = '🍺';
UPDATE categorias SET icono = 'mdiGlassWine'        WHERE icono = '🍷';
UPDATE categorias SET icono = 'mdiGlassCocktail'    WHERE icono = '🍹';
UPDATE categorias SET icono = 'mdiCupOutline'       WHERE icono = '🥛';
UPDATE categorias SET icono = 'mdiGlassFlute'       WHERE icono = '🍸';
UPDATE categorias SET icono = 'mdiBottleWine'       WHERE icono = '🧉';
-- Postres
UPDATE categorias SET icono = 'mdiCakeVariant'      WHERE icono = '🍰';
UPDATE categorias SET icono = 'mdiCakeLayered'      WHERE icono = '🎂';
UPDATE categorias SET icono = 'mdiCakeVariant'      WHERE icono = '🍮';
UPDATE categorias SET icono = 'mdiIceCream'         WHERE icono = '🍦';
UPDATE categorias SET icono = 'mdiCupcake'          WHERE icono = '🧁';
UPDATE categorias SET icono = 'mdiCookieOutline'    WHERE icono = '🍩';
UPDATE categorias SET icono = 'mdiCookieOutline'    WHERE icono = '🍪';
UPDATE categorias SET icono = 'mdiCakeVariant'      WHERE icono = '🍫';
UPDATE categorias SET icono = 'mdiCandy'            WHERE icono = '🍬';
UPDATE categorias SET icono = 'mdiCandyOutline'     WHERE icono = '🍭';
UPDATE categorias SET icono = 'mdiChartPie'         WHERE icono = '🥧';
UPDATE categorias SET icono = 'mdiFoodVariant'      WHERE icono = '🍡';
-- Extras
UPDATE categorias SET icono = 'mdiStar'             WHERE icono = '⭐';
UPDATE categorias SET icono = 'mdiFireCircle'       WHERE icono = '🔥';
UPDATE categorias SET icono = 'mdiDiamond'          WHERE icono = '💎';
UPDATE categorias SET icono = 'mdiLeaf'             WHERE icono = '🌿';
UPDATE categorias SET icono = 'mdiMedal'            WHERE icono = '🥇';
UPDATE categorias SET icono = 'mdiHeart'            WHERE icono = '❤️';
UPDATE categorias SET icono = 'mdiChiliHot'         WHERE icono = '🌶️';
UPDATE categorias SET icono = 'mdiLeaf'             WHERE icono = '🥑';
UPDATE categorias SET icono = 'mdiAutoFix'          WHERE icono = '✨';
UPDATE categorias SET icono = 'mdiNewBox'           WHERE icono = '🆕';
UPDATE categorias SET icono = 'mdiTarget'           WHERE icono = '🎯';

-- 3. Fallback: cualquier emoji no mapeado → mdiFoodVariant
UPDATE categorias
  SET icono = 'mdiFoodVariant'
  WHERE icono IS NOT NULL
    AND icono NOT LIKE 'mdi%';
