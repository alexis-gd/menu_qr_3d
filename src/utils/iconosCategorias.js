/**
 * Íconos MDI curados para el picker de categorías.
 * Cada entrada: { nombre: string (export de @mdi/js), path: string SVG, label: string }
 * Se importan individualmente para que el build sea tree-shakeable.
 */
import {
  mdiPizza, mdiHamburger, mdiTaco, mdiFoodVariant, mdiBone, mdiFoodSteak,
  mdiFoodDrumstick, mdiNoodles, mdiFoodHotDog, mdiSilverware, mdiFish,
  mdiCupWater, mdiCoffee, mdiTeaOutline, mdiGlassMugVariant, mdiGlassWine,
  mdiGlassCocktail, mdiCupOutline, mdiGlassFlute, mdiTea, mdiCakeVariant,
  mdiCakeLayered, mdiIceCream, mdiCupcake, mdiCookieOutline, mdiCandy,
  mdiCandyOutline, mdiChartPie, mdiStar, mdiFireCircle, mdiDiamond, mdiLeaf,
  mdiMedal, mdiHeart, mdiChiliHot, mdiTarget, mdiAutoFix, mdiNewBox,
  mdiRice, mdiBowl, mdiPotSteam, mdiWrap, mdiFoodForkDrink, mdiBottleWine,
} from '@mdi/js'

/** Mapa nombre-export → path SVG. Usado para lookup dinámico por nombre guardado en BD. */
export const ICONOS_MDI = {
  mdiPizza, mdiHamburger, mdiTaco, mdiFoodVariant, mdiBone, mdiFoodSteak,
  mdiFoodDrumstick, mdiNoodles, mdiFoodHotDog, mdiSilverware, mdiFish,
  mdiCupWater, mdiCoffee, mdiTeaOutline, mdiGlassMugVariant, mdiGlassWine,
  mdiGlassCocktail, mdiCupOutline, mdiGlassFlute, mdiTea, mdiCakeVariant,
  mdiCakeLayered, mdiIceCream, mdiCupcake, mdiCookieOutline, mdiCandy,
  mdiCandyOutline, mdiChartPie, mdiStar, mdiFireCircle, mdiDiamond, mdiLeaf,
  mdiMedal, mdiHeart, mdiChiliHot, mdiTarget, mdiAutoFix, mdiNewBox,
  mdiRice, mdiBowl, mdiPotSteam, mdiWrap, mdiFoodForkDrink, mdiBottleWine,
}

/** Resuelve un nombre MDI a su path SVG. Fallback a mdiFoodVariant. */
export const resolverIcono = (nombre) => ICONOS_MDI[nombre] ?? mdiFoodVariant

/** Grupos para el picker del admin (equivale a los anteriores emojiGrupos). */
export const ICONO_GRUPOS = [
  {
    nombre: 'Platos',
    iconos: [
      { key: 'mdiPizza',         path: mdiPizza,         label: 'Pizza' },
      { key: 'mdiHamburger',     path: mdiHamburger,     label: 'Hamburguesa' },
      { key: 'mdiTaco',          path: mdiTaco,           label: 'Taco' },
      { key: 'mdiWrap',          path: mdiWrap,           label: 'Wrap' },
      { key: 'mdiFoodSteak',     path: mdiFoodSteak,     label: 'Filete' },
      { key: 'mdiBone',          path: mdiBone,           label: 'Costilla' },
      { key: 'mdiFoodDrumstick', path: mdiFoodDrumstick, label: 'Pierna' },
      { key: 'mdiFoodHotDog',    path: mdiFoodHotDog,    label: 'Hot Dog' },
      { key: 'mdiNoodles',       path: mdiNoodles,       label: 'Noodles' },
      { key: 'mdiRice',          path: mdiRice,           label: 'Arroz' },
      { key: 'mdiBowl',          path: mdiBowl,           label: 'Bowl' },
      { key: 'mdiPotSteam',      path: mdiPotSteam,      label: 'Olla' },
      { key: 'mdiFoodVariant',   path: mdiFoodVariant,   label: 'Comida' },
    ],
  },
  {
    nombre: 'Mariscos',
    iconos: [
      { key: 'mdiFish',          path: mdiFish,          label: 'Pescado' },
      { key: 'mdiSilverware',    path: mdiSilverware,    label: 'Sushi' },
      { key: 'mdiFoodForkDrink', path: mdiFoodForkDrink, label: 'Mariscos' },
    ],
  },
  {
    nombre: 'Bebidas',
    iconos: [
      { key: 'mdiCupWater',        path: mdiCupWater,        label: 'Bebida' },
      { key: 'mdiCoffee',          path: mdiCoffee,          label: 'Café' },
      { key: 'mdiTeaOutline',      path: mdiTeaOutline,      label: 'Té' },
      { key: 'mdiTea',             path: mdiTea,             label: 'Infusión' },
      { key: 'mdiGlassMugVariant', path: mdiGlassMugVariant, label: 'Cerveza' },
      { key: 'mdiGlassWine',       path: mdiGlassWine,       label: 'Vino' },
      { key: 'mdiBottleWine',      path: mdiBottleWine,      label: 'Botella' },
      { key: 'mdiGlassCocktail',   path: mdiGlassCocktail,   label: 'Cóctel' },
      { key: 'mdiGlassFlute',      path: mdiGlassFlute,      label: 'Martini' },
      { key: 'mdiCupOutline',      path: mdiCupOutline,      label: 'Vaso' },
    ],
  },
  {
    nombre: 'Postres',
    iconos: [
      { key: 'mdiCakeVariant',   path: mdiCakeVariant,   label: 'Pastel' },
      { key: 'mdiCakeLayered',   path: mdiCakeLayered,   label: 'Layered' },
      { key: 'mdiCupcake',       path: mdiCupcake,       label: 'Cupcake' },
      { key: 'mdiIceCream',      path: mdiIceCream,      label: 'Helado' },
      { key: 'mdiCookieOutline', path: mdiCookieOutline, label: 'Galleta' },
      { key: 'mdiCandy',         path: mdiCandy,         label: 'Dulce' },
      { key: 'mdiCandyOutline',  path: mdiCandyOutline,  label: 'Paleta' },
      { key: 'mdiChartPie',      path: mdiChartPie,      label: 'Pay' },
    ],
  },
  {
    nombre: 'Extras',
    iconos: [
      { key: 'mdiStar',        path: mdiStar,        label: 'Estrella' },
      { key: 'mdiFireCircle',  path: mdiFireCircle,  label: 'Fuego' },
      { key: 'mdiDiamond',     path: mdiDiamond,     label: 'Premium' },
      { key: 'mdiLeaf',        path: mdiLeaf,        label: 'Vegano' },
      { key: 'mdiMedal',       path: mdiMedal,       label: 'Top' },
      { key: 'mdiHeart',       path: mdiHeart,       label: 'Favorito' },
      { key: 'mdiChiliHot',    path: mdiChiliHot,    label: 'Picante' },
      { key: 'mdiTarget',      path: mdiTarget,      label: 'Oferta' },
      { key: 'mdiAutoFix',     path: mdiAutoFix,     label: 'Especial' },
      { key: 'mdiNewBox',      path: mdiNewBox,      label: 'Nuevo' },
    ],
  },
]
