/**
 * Wrapper sobre window.gtag (Google Analytics 4).
 * Si GA no está cargado (local / sin ID) las llamadas se ignoran silenciosamente.
 */

const gtag = (...args) => {
  if (typeof window.gtag === 'function') window.gtag(...args)
}

/**
 * Dispara view_item cuando el cliente abre el modal de un producto.
 * @param {Object} producto  — objeto producto de la API
 * @param {string} categoria — nombre de la categoría (opcional)
 */
export const trackViewItem = (producto, categoria = '') => {
  gtag('event', 'view_item', {
    currency: 'MXN',
    value: Number(producto.precio),
    items: [{
      item_id:       String(producto.id),
      item_name:     producto.nombre,
      item_category: categoria,
      price:         Number(producto.precio),
      quantity:      1,
    }],
  })
}

/**
 * Dispara add_to_cart cuando el cliente agrega un producto al carrito.
 * @param {Object} producto       — objeto producto de la API
 * @param {number} precio_unitario — precio real pagado (incluye extras de opciones)
 * @param {string} categoria       — nombre de la categoría (opcional)
 */
export const trackAddToCart = (producto, precio_unitario, categoria = '') => {
  gtag('event', 'add_to_cart', {
    currency: 'MXN',
    value: precio_unitario,
    items: [{
      item_id:       String(producto.id),
      item_name:     producto.nombre,
      item_category: categoria,
      price:         precio_unitario,
      quantity:      1,
    }],
  })
}
