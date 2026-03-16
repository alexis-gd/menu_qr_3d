/**
 * Íconos MDI curados para el picker de categorías.
 * Cada entrada: { key: string (export de @mdi/js), path: string SVG, label: string }
 * Se importan individualmente para que el build sea tree-shakeable.
 */
import {
  // Platos
  mdiPizza, mdiHamburger, mdiTaco, mdiFoodVariant, mdiBone, mdiFoodSteak,
  mdiFoodDrumstick, mdiNoodles, mdiFoodHotDog, mdiSilverware, mdiRice,
  mdiBowl, mdiPotSteam, mdiWrap, mdiFoodCroissant, mdiBreadSlice,
  mdiSausage, mdiEggFried, mdiGrill, mdiFood, mdiSilverwareForkKnife,
  // Mariscos
  mdiFish, mdiFoodForkDrink,
  // Vegetariano / frutas
  mdiCarrot, mdiCorn, mdiMushroom, mdiChiliHot,
  mdiFruitCherries, mdiFruitCitrus, mdiFruitPineapple, mdiFruitWatermelon, mdiFruitGrapes,
  mdiLeaf,
  // Bebidas
  mdiCupWater, mdiCoffee, mdiTeaOutline, mdiTea, mdiGlassMugVariant, mdiGlassWine,
  mdiGlassCocktail, mdiCupOutline, mdiGlassFlute, mdiBottleWine,
  // Postres / panadería
  mdiCakeVariant, mdiCakeLayered, mdiIceCream, mdiCupcake, mdiCookieOutline,
  mdiCandy, mdiCandyOutline, mdiChartPie, mdiPretzel, mdiPopcorn,
  // Extras / badges
  mdiStar, mdiFireCircle, mdiDiamond, mdiMedal, mdiHeart,
  mdiTarget, mdiAutoFix, mdiNewBox, mdiCheese,
} from '@mdi/js'

/** Mapa nombre-export → path SVG. Usado para lookup dinámico por nombre guardado en BD. */
export const ICONOS_MDI = {
  mdiPizza, mdiHamburger, mdiTaco, mdiFoodVariant, mdiBone, mdiFoodSteak,
  mdiFoodDrumstick, mdiNoodles, mdiFoodHotDog, mdiSilverware, mdiRice,
  mdiBowl, mdiPotSteam, mdiWrap, mdiFoodCroissant, mdiBreadSlice,
  mdiSausage, mdiEggFried, mdiGrill, mdiFood, mdiSilverwareForkKnife,
  mdiFish, mdiFoodForkDrink,
  mdiCarrot, mdiCorn, mdiMushroom, mdiChiliHot,
  mdiFruitCherries, mdiFruitCitrus, mdiFruitPineapple, mdiFruitWatermelon, mdiFruitGrapes,
  mdiLeaf,
  mdiCupWater, mdiCoffee, mdiTeaOutline, mdiTea, mdiGlassMugVariant, mdiGlassWine,
  mdiGlassCocktail, mdiCupOutline, mdiGlassFlute, mdiBottleWine,
  mdiCakeVariant, mdiCakeLayered, mdiIceCream, mdiCupcake, mdiCookieOutline,
  mdiCandy, mdiCandyOutline, mdiChartPie, mdiPretzel, mdiPopcorn,
  mdiStar, mdiFireCircle, mdiDiamond, mdiMedal, mdiHeart,
  mdiTarget, mdiAutoFix, mdiNewBox, mdiCheese,
}

/** Resuelve un nombre MDI a su path SVG. Fallback a mdiFoodVariant. */
export const resolverIcono = (nombre) => ICONOS_MDI[nombre] ?? mdiFoodVariant

/** Grupos para el picker del admin. */
export const ICONO_GRUPOS = [
  {
    nombre: 'Platos',
    iconos: [
      { key: 'mdiPizza',             path: mdiPizza,             label: 'Pizza' },
      { key: 'mdiHamburger',         path: mdiHamburger,         label: 'Hamburguesa' },
      { key: 'mdiTaco',              path: mdiTaco,              label: 'Taco' },
      { key: 'mdiWrap',              path: mdiWrap,              label: 'Wrap / Burrito' },
      { key: 'mdiFoodSteak',         path: mdiFoodSteak,         label: 'Filete' },
      { key: 'mdiBone',              path: mdiBone,              label: 'Costilla' },
      { key: 'mdiFoodDrumstick',     path: mdiFoodDrumstick,     label: 'Pierna' },
      { key: 'mdiSausage',           path: mdiSausage,           label: 'Salchicha' },
      { key: 'mdiFoodHotDog',        path: mdiFoodHotDog,        label: 'Hot Dog' },
      { key: 'mdiNoodles',           path: mdiNoodles,           label: 'Noodles / Pasta' },
      { key: 'mdiRice',              path: mdiRice,              label: 'Arroz' },
      { key: 'mdiBowl',              path: mdiBowl,              label: 'Bowl' },
      { key: 'mdiPotSteam',          path: mdiPotSteam,          label: 'Olla / Caldo' },
      { key: 'mdiGrill',             path: mdiGrill,             label: 'Parrilla / BBQ' },
      { key: 'mdiFoodCroissant',     path: mdiFoodCroissant,     label: 'Croissant' },
      { key: 'mdiBreadSlice',        path: mdiBreadSlice,        label: 'Pan' },
      { key: 'mdiEggFried',          path: mdiEggFried,          label: 'Huevo' },
      { key: 'mdiCheese',            path: mdiCheese,            label: 'Queso' },
      { key: 'mdiFood',              path: mdiFood,              label: 'Comida general' },
      { key: 'mdiFoodVariant',       path: mdiFoodVariant,       label: 'Platillo' },
      { key: 'mdiSilverwareForkKnife', path: mdiSilverwareForkKnife, label: 'Cubiertos' },
      { key: 'mdiSilverware',        path: mdiSilverware,        label: 'Sushi / Cubiertos' },
    ],
  },
  {
    nombre: 'Mariscos / Mar',
    iconos: [
      { key: 'mdiFish',          path: mdiFish,          label: 'Pescado' },
      { key: 'mdiFoodForkDrink', path: mdiFoodForkDrink, label: 'Mariscos' },
    ],
  },
  {
    nombre: 'Vegetariano / Frutas',
    iconos: [
      { key: 'mdiCarrot',           path: mdiCarrot,           label: 'Zanahoria / Verdura' },
      { key: 'mdiCorn',             path: mdiCorn,             label: 'Maíz / Elote' },
      { key: 'mdiMushroom',         path: mdiMushroom,         label: 'Champiñón' },
      { key: 'mdiChiliHot',         path: mdiChiliHot,         label: 'Chile / Picante' },
      { key: 'mdiLeaf',             path: mdiLeaf,             label: 'Vegano / Natural' },
      { key: 'mdiFruitCherries',    path: mdiFruitCherries,    label: 'Cerezas / Fruta' },
      { key: 'mdiFruitCitrus',      path: mdiFruitCitrus,      label: 'Cítrico / Naranja' },
      { key: 'mdiFruitPineapple',   path: mdiFruitPineapple,   label: 'Piña' },
      { key: 'mdiFruitWatermelon',  path: mdiFruitWatermelon,  label: 'Sandía' },
      { key: 'mdiFruitGrapes',      path: mdiFruitGrapes,      label: 'Uvas' },
      { key: 'mdiPopcorn',          path: mdiPopcorn,          label: 'Palomitas / Snacks' },
    ],
  },
  {
    nombre: 'Bebidas',
    iconos: [
      { key: 'mdiCupWater',        path: mdiCupWater,        label: 'Bebida / Agua' },
      { key: 'mdiCoffee',          path: mdiCoffee,          label: 'Café' },
      { key: 'mdiTeaOutline',      path: mdiTeaOutline,      label: 'Té' },
      { key: 'mdiTea',             path: mdiTea,             label: 'Infusión' },
      { key: 'mdiGlassMugVariant', path: mdiGlassMugVariant, label: 'Cerveza' },
      { key: 'mdiGlassWine',       path: mdiGlassWine,       label: 'Vino' },
      { key: 'mdiBottleWine',      path: mdiBottleWine,      label: 'Botella' },
      { key: 'mdiGlassCocktail',   path: mdiGlassCocktail,   label: 'Cóctel' },
      { key: 'mdiGlassFlute',      path: mdiGlassFlute,      label: 'Copa / Martini' },
      { key: 'mdiCupOutline',      path: mdiCupOutline,      label: 'Vaso' },
    ],
  },
  {
    nombre: 'Postres / Panadería',
    iconos: [
      { key: 'mdiCakeVariant',   path: mdiCakeVariant,   label: 'Pastel' },
      { key: 'mdiCakeLayered',   path: mdiCakeLayered,   label: 'Pastel multicapa' },
      { key: 'mdiCupcake',       path: mdiCupcake,       label: 'Cupcake' },
      { key: 'mdiIceCream',      path: mdiIceCream,      label: 'Helado' },
      { key: 'mdiCookieOutline', path: mdiCookieOutline, label: 'Galleta' },
      { key: 'mdiCandy',         path: mdiCandy,         label: 'Dulce' },
      { key: 'mdiCandyOutline',  path: mdiCandyOutline,  label: 'Paleta' },
      { key: 'mdiChartPie',      path: mdiChartPie,      label: 'Pay / Tarta' },
      { key: 'mdiPretzel',       path: mdiPretzel,       label: 'Pretzel / Pan' },
      { key: 'mdiFoodCroissant', path: mdiFoodCroissant, label: 'Croissant / Bollería' },
    ],
  },
  {
    nombre: 'Badges / Destacados',
    iconos: [
      { key: 'mdiStar',        path: mdiStar,        label: 'Estrella / Top' },
      { key: 'mdiFireCircle',  path: mdiFireCircle,  label: 'Lo más pedido' },
      { key: 'mdiDiamond',     path: mdiDiamond,     label: 'Premium' },
      { key: 'mdiMedal',       path: mdiMedal,       label: 'Mejor precio' },
      { key: 'mdiHeart',       path: mdiHeart,       label: 'Favorito' },
      { key: 'mdiTarget',      path: mdiTarget,      label: 'Oferta / Promo' },
      { key: 'mdiAutoFix',     path: mdiAutoFix,     label: 'Chef especial' },
      { key: 'mdiNewBox',      path: mdiNewBox,      label: 'Nuevo' },
      { key: 'mdiChiliHot',    path: mdiChiliHot,    label: 'Picante' },
    ],
  },
]
