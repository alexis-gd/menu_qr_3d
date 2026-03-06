<template>
  <div class="admin-panel" :class="{ 'tema-oscuro-admin': formRest.tema === 'oscuro' }" :style="{ '--accent': temaActualData.accent }">
    <!-- ═══ Header ═══ -->
    <header class="panel-header">
      <div class="header-left">
        <img v-if="restaurante?.logo_url" :src="restaurante.logo_url" class="header-logo-img" alt="logo" />
        <span v-else class="header-icon">🍽️</span>
        <div>
          <h1 class="header-title">{{ restaurante?.nombre || 'Mi Restaurante' }}</h1>
          <span class="header-sub">Panel de administración</span>
        </div>
      </div>
      <button @click="logout" class="btn-logout">Salir</button>
    </header>

    <!-- Loading inicial -->
    <div v-if="cargandoInicial" class="loading-screen">
      <div class="spinner-lg"></div>
      <p>Cargando panel...</p>
    </div>

    <div v-else-if="errorInicial" class="error-screen">
      <p>{{ errorInicial }}</p>
    </div>

    <div v-else class="panel-body">
      <!-- ═══ Tabs ═══ -->
      <nav class="tab-nav">
        <button
          v-for="tab in tabs" :key="tab.id"
          :class="['tab-btn', { active: tabActivo === tab.id }]"
          @click="tabActivo = tab.id"
        >
          <span class="tab-icon">{{ tab.icon }}</span>
          <span class="tab-label">{{ tab.label }}</span>
        </button>
      </nav>

      <!-- Notificación flotante -->
      <transition name="notif-anim">
        <div v-if="notif" :class="['notif', `notif-${notif.tipo}`]">{{ notif.texto }}</div>
      </transition>

      <!-- ════════════════════════════════
           TAB: PLATILLOS
      ════════════════════════════════ -->
      <div v-show="tabActivo === 'platillos'" class="tab-content">
        <!-- Formulario nuevo platillo -->
        <div class="card">
          <div class="card-header collapsible" @click="formAbierto = !formAbierto">
            <h2>+ Agregar platillo</h2>
            <span class="chevron">{{ formAbierto ? '▲' : '▼' }}</span>
          </div>
          <div v-show="formAbierto" class="card-body">
            <div class="form-grid">
              <div class="field">
                <label>Categoría *</label>
                <select v-model="formProd.categoria_id">
                  <option value="" disabled>Selecciona categoría</option>
                  <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                    {{ cat.icono || '' }} {{ cat.nombre }}
                  </option>
                </select>
              </div>
              <div class="field">
                <label>Nombre del platillo *</label>
                <input :value="formProd.nombre" @input="formProd.nombre = ucfirst($event.target.value)" placeholder="Ej: Tacos al Pastor" />
              </div>
              <div class="field">
                <label>Precio *</label>
                <input v-model="formProd.precio" type="number" min="0" step="0.01" placeholder="0.00" />
              </div>
              <div class="field field-full">
                <label>Descripción breve</label>
                <textarea :value="formProd.descripcion" @input="formProd.descripcion = ucfirst($event.target.value)" rows="2" placeholder="Descripción opcional..."></textarea>
              </div>
              <div class="field field-full">
                <button @click="crearProducto" class="btn-primary" :disabled="guardando">
                  {{ guardando ? 'Guardando...' : '+ Agregar platillo' }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Lista de productos -->
        <div class="card">
          <div class="card-header">
            <h2>Platillos del menú</h2>
            <span class="count-badge">{{ productos.length }}</span>
          </div>
          <div class="card-body no-pad">
            <div v-if="loadingProductos" class="loading-inline"><div class="spinner"></div></div>
            <div v-else-if="!productos.length" class="empty-state">
              <span>🍽️</span>
              <p>Sin platillos todavía.<br>Agrega el primero arriba.</p>
            </div>
            <div v-else class="prod-lista">
              <div
                v-for="prod in productosOrdenados"
                :key="prod.id"
                class="prod-item"
                :class="{ editing: prodEditando === prod.id }"
              >
                <!-- ── Modo edición ── -->
                <div v-if="prodEditando === prod.id" class="prod-edit-form">
                  <div class="edit-thumb">
                    <img v-if="prod.foto_principal" :src="thumbUrl(prod.foto_principal)" :alt="prod.nombre" />
                    <span v-else>📷</span>
                  </div>
                  <div class="edit-fields">
                    <select v-model="formEdit.categoria_id">
                      <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
                    </select>
                    <input :value="formEdit.nombre" @input="formEdit.nombre = ucfirst($event.target.value)" placeholder="Nombre" />
                    <input v-model="formEdit.precio" type="number" min="0" step="0.01" placeholder="Precio" />
                    <textarea :value="formEdit.descripcion" @input="formEdit.descripcion = ucfirst($event.target.value)" rows="2" placeholder="Descripción"></textarea>
                    <div class="edit-actions">
                      <button @click="guardarEdicionProducto(prod.id)" class="btn-save">✓ Guardar</button>
                      <button @click="cancelarEdicion" class="btn-cancel">✕ Cancelar</button>
                    </div>
                  </div>
                </div>

                <!-- ── Modo normal ── -->
                <template v-else>
                  <!-- Miniatura (click → preview) -->
                  <div class="prod-thumb" @click="abrirPreview(prod)" :title="'Ver foto'">
                    <img
                      v-if="prod.foto_principal"
                      :src="thumbUrl(prod.foto_principal)"
                      :alt="prod.nombre"
                      @error="($e) => $e.target.style.display='none'"
                    />
                    <span v-else class="thumb-empty">📷</span>
                    <div v-if="prod.foto_principal" class="thumb-overlay">👁</div>
                  </div>

                  <!-- Info -->
                  <div class="prod-info">
                    <strong class="prod-nombre">{{ prod.nombre }}</strong>
                    <span class="prod-cat">{{ catMap[prod.categoria_id] || '—' }}</span>
                    <span class="prod-precio">${{ Number(prod.precio).toFixed(2) }}</span>
                  </div>

                  <!-- Badges -->
                  <div class="prod-badges">
                    <span v-if="prod.tiene_ar" class="badge badge-3d">3D ✓</span>
                    <span v-else class="badge badge-no3d">Sin 3D</span>
                  </div>

                  <!-- Acciones -->
                  <div class="prod-actions">
                    <button @click="iniciarEdicion(prod)" class="btn-icon btn-edit" title="Editar platillo">✏️</button>
                    <label class="btn-icon btn-foto" title="Subir foto">
                      📷 <input type="file" multiple accept="image/*" @change="subirFotos(prod.id, $event)" hidden />
                    </label>
                    <label
                      v-if="!prod.tiene_ar"
                      class="btn-icon btn-3d"
                      title="Subir modelo 3D (.glb)"
                    >
                      📦 <input type="file" accept=".glb" @change="subirGlb(prod.id, $event)" hidden />
                    </label>
                    <button @click="eliminarProducto(prod.id)" class="btn-icon btn-del" title="Eliminar">🗑</button>
                  </div>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ════════════════════════════════
           TAB: CATEGORÍAS
      ════════════════════════════════ -->
      <div v-show="tabActivo === 'categorias'" class="tab-content">
        <div class="card">
          <div class="card-header">
            <h2>Nueva categoría</h2>
          </div>
          <div class="card-body">
            <div class="form-row">
              <div class="field" style="flex:1">
                <label>Nombre</label>
                <input :value="formCat.nombre" @input="formCat.nombre = ucfirst($event.target.value)" placeholder="Ej: Entradas, Bebidas..." @keyup.enter="crearCategoria" />
              </div>
              <div class="field">
                <label>Ícono</label>
                <div class="emoji-wrap">
                  <button type="button" class="emoji-btn" @click.stop="togglePicker('nuevo')">
                    <span class="emoji-display">{{ formCat.icono || '📋' }}</span>
                    <span class="picker-caret">▾</span>
                  </button>
                  <div v-if="pickerAbierto === 'nuevo'" class="emoji-picker" @click.stop>
                    <div v-for="g in emojiGrupos" :key="g.nombre" class="emoji-grupo">
                      <div class="emoji-grupo-titulo">{{ g.nombre }}</div>
                      <div class="emoji-grid">
                        <button v-for="e in g.emojis" :key="e" type="button" class="emoji-opt"
                          :class="{ selected: formCat.icono === e }"
                          @click="seleccionarEmoji(e, 'nuevo')">{{ e }}</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="field field-btn">
                <label>&nbsp;</label>
                <button @click="crearCategoria" class="btn-primary">Agregar</button>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h2>Categorías</h2>
            <span class="count-badge">{{ categorias.length }}</span>
          </div>
          <div class="card-body no-pad">
            <div v-if="!categorias.length" class="empty-state" style="padding:32px">
              <span>📋</span>
              <p>Sin categorías todavía.</p>
            </div>
            <div v-else class="cat-lista">
              <div v-for="(cat, idx) in categorias" :key="cat.id" class="cat-item">
                <!-- Modo edición -->
                <div v-if="catEditando === cat.id" class="cat-edit-form">
                  <div class="emoji-wrap">
                    <button type="button" class="emoji-btn emoji-btn-sm" @click.stop="togglePicker(cat.id)">
                      <span class="emoji-display">{{ formCatEdit.icono || '📋' }}</span>
                      <span class="picker-caret">▾</span>
                    </button>
                    <div v-if="pickerAbierto === cat.id" class="emoji-picker emoji-picker-right" @click.stop>
                      <div v-for="g in emojiGrupos" :key="g.nombre" class="emoji-grupo">
                        <div class="emoji-grupo-titulo">{{ g.nombre }}</div>
                        <div class="emoji-grid">
                          <button v-for="e in g.emojis" :key="e" type="button" class="emoji-opt"
                            :class="{ selected: formCatEdit.icono === e }"
                            @click="seleccionarEmoji(e, cat.id)">{{ e }}</button>
                        </div>
                      </div>
                    </div>
                  </div>
                  <input :value="formCatEdit.nombre" @input="formCatEdit.nombre = ucfirst($event.target.value)" placeholder="Nombre" class="input-nombre" @keyup.enter="guardarEdicionCategoria(cat.id)" />
                  <button @click="guardarEdicionCategoria(cat.id)" class="btn-save-sm">✓</button>
                  <button @click="catEditando = null" class="btn-cancel-sm">✕</button>
                </div>

                <!-- Modo normal -->
                <template v-else>
                  <span class="cat-emoji">{{ cat.icono || '📋' }}</span>
                  <span class="cat-nombre">{{ cat.nombre }}</span>
                  <span class="cat-count">{{ conteoProductos(cat.id) }} platillo(s)</span>
                  <div class="cat-ord-btns">
                    <button @click="moverCategoria(idx, -1)" class="btn-ord" :disabled="idx === 0" title="Subir">▲</button>
                    <button @click="moverCategoria(idx, 1)" class="btn-ord" :disabled="idx === categorias.length - 1" title="Bajar">▼</button>
                  </div>
                  <button @click="iniciarEdicionCategoria(cat)" class="btn-icon btn-edit" title="Editar">✏️</button>
                  <button @click="eliminarCategoria(cat.id)" class="btn-icon btn-del" title="Eliminar">🗑</button>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ════════════════════════════════
           TAB: APARIENCIA & QR
      ════════════════════════════════ -->
      <div v-show="tabActivo === 'apariencia'" class="tab-content">
        <!-- Info restaurante -->
        <div class="card">
          <div class="card-header">
            <h2>Información del restaurante</h2>
          </div>
          <div class="card-body form-grid">
            <div class="field">
              <label>Nombre del restaurante</label>
              <input v-model="formRest.nombre" placeholder="Nombre visible en el menú" />
            </div>
            <div class="field field-full">
              <label>Descripción</label>
              <textarea v-model="formRest.descripcion" rows="2" placeholder="Descripción breve para el menú"></textarea>
            </div>
          </div>
        </div>

        <!-- Logo del restaurante -->
        <div class="card">
          <div class="card-header">
            <h2>Logo del restaurante</h2>
          </div>
          <div class="card-body">
            <p class="helper-text">Sube el logo que aparecerá en el menú.</p>
            <div class="logo-upload-row">
              <div class="logo-preview-wrap">
                <img v-if="restaurante?.logo_url" :src="restaurante.logo_url" class="logo-preview-img" alt="Logo actual" />
                <div v-else class="logo-preview-empty">🍽️</div>
              </div>
              <div class="logo-upload-actions">
                <label class="btn-upload-logo" :class="{ loading: logoSubiendo }">
                  <input type="file" accept="image/jpeg,image/png,image/webp" @change="uploadLogo" :disabled="logoSubiendo" style="display:none" />
                  {{ logoSubiendo ? 'Subiendo...' : '📂 Subir logo' }}
                </label>
                <span class="logo-hint">JPG, PNG o WebP · máx. 2 MB</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Selector de tema -->
        <div class="card">
          <div class="card-header">
            <h2>Tema visual del menú</h2>
          </div>
          <div class="card-body">
            <p class="helper-text">Elige el estilo que mejor represente a tu restaurante.</p>
            <div class="temas-grid">
              <div
                v-for="tema in temas" :key="tema.id"
                :class="['tema-card', { selected: formRest.tema === tema.id }]"
                :style="{ background: tema.bg, borderColor: formRest.tema === tema.id ? tema.accent : '#e0e0e0' }"
                @click="formRest.tema = tema.id"
              >
                <!-- Miniatura preview -->
                <div class="tema-mockup" :style="{ background: tema.headerBg }">
                  <div class="mock-title" :style="{ color: tema.headerText }">🍽️ Restaurante</div>
                  <div class="mock-card" :style="{ background: tema.cardBg }">
                    <div class="mock-img" :style="{ background: tema.accent + '40' }"></div>
                    <div class="mock-info">
                      <div class="mock-name" :style="{ background: tema.text + '25' }"></div>
                      <div class="mock-price" :style="{ color: tema.accent }">$85</div>
                    </div>
                  </div>
                </div>
                <div class="tema-label" :style="{ color: tema.text }">
                  <strong>{{ tema.nombre }}</strong>
                  <span>{{ tema.desc }}</span>
                  <span v-if="formRest.tema === tema.id" class="tema-activo">✓ Activo</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- QR del menú -->
        <div class="card">
          <div class="card-header">
            <h2>🔲 Código QR del menú</h2>
          </div>
          <div class="card-body qr-dashboard-body">
            <p class="helper-text">Imprime esta tarjeta y colócala en tus mesas. Tus clientes escanean el QR para ver el menú.</p>
            <div class="qr-url-box">
              <code class="qr-url-text">{{ menuUrl }}</code>
              <button @click="copiarUrl" class="btn-copy">{{ copiado ? '✓ Copiado' : 'Copiar' }}</button>
            </div>

            <div class="qr-card-layout">
              <!-- Card preview -->
              <div class="qr-card-preview-col">
                <p class="qr-preview-label">Vista previa</p>
                <div class="qr-card-dm" ref="qrCardDmEl">
                  <div class="qr-card-dm-hdr" :style="{ background: temaActualData.headerBg }">
                    <div class="qr-hdr-inner">
                      <img v-if="restaurante?.logo_url" :src="restaurante.logo_url" class="qr-hdr-logo" alt="logo" />
                      <span v-else class="qr-hdr-emoji">🍽️</span>
                      <span class="qr-hdr-nombre" :style="{ color: temaActualData.headerText }">{{ restaurante?.nombre }}</span>
                    </div>
                  </div>
                  <div class="qr-card-dm-body">
                    <h3 class="qr-dm-title">Escanea el menú</h3>
                    <p v-if="formRest.qr_frase_activa" class="qr-dm-frase">"{{ formRest.qr_frase }}"</p>
                    <div class="qr-dm-qr-wrap" :style="{ borderColor: temaActualData.accent + '33', boxShadow: `0 5px 18px ${temaActualData.accent}20` }">
                      <img v-if="qrDataUrl" :src="qrDataUrl" class="qr-dm-img" alt="QR" />
                      <div v-else class="qr-dm-placeholder"><div class="spinner"></div></div>
                    </div>
                    <div v-if="formRest.qr_wifi_activo" class="qr-dm-wifi" :style="{ color: temaActualData.accent }">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M5 12.55a11 11 0 0 1 14.08 0"/>
                        <path d="M1.42 9a16 16 0 0 1 21.16 0"/>
                        <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/>
                        <line x1="12" y1="20" x2="12.01" y2="20"/>
                      </svg>
                      <div class="qr-wifi-texts">
                        <span class="qr-wifi-net">{{ formRest.qr_wifi_nombre || 'Nombre de red' }}</span>
                        <span class="qr-wifi-pass">{{ formRest.qr_wifi_clave || '••••••••' }}</span>
                      </div>
                    </div>
                  </div>
                  <div class="qr-card-dm-bar" :style="{ background: temaActualData.headerBg }"></div>
                </div>
              </div>

              <!-- Controls -->
              <div class="qr-card-controls-col">
                <h4 class="qr-ctrl-title">Personalizar tarjeta</h4>

                <div class="qr-ctrl-group">
                  <div class="qr-ctrl-header">
                    <span class="qr-ctrl-label">Frase motivacional</span>
                    <label class="sw">
                      <input type="checkbox" v-model="formRest.qr_frase_activa" />
                      <span class="sw-track" :style="formRest.qr_frase_activa ? { background: formRest.tema === 'oscuro' ? '#2a2a6a' : temaActualData.accent } : {}"></span>
                    </label>
                  </div>
                  <input v-if="formRest.qr_frase_activa" :value="formRest.qr_frase" @input="formRest.qr_frase = ucfirst($event.target.value)" class="qr-ctrl-input" maxlength="60" placeholder="Ej: Delicioso desde el primer vistazo" />
                </div>

                <div class="qr-ctrl-group">
                  <div class="qr-ctrl-header">
                    <span class="qr-ctrl-label">Info WiFi</span>
                    <label class="sw">
                      <input type="checkbox" v-model="formRest.qr_wifi_activo" />
                      <span class="sw-track" :style="formRest.qr_wifi_activo ? { background: formRest.tema === 'oscuro' ? '#2a2a6a' : temaActualData.accent } : {}"></span>
                    </label>
                  </div>
                  <template v-if="formRest.qr_wifi_activo">
                    <input v-model="formRest.qr_wifi_nombre" class="qr-ctrl-input" placeholder="Nombre de red" />
                    <input v-model="formRest.qr_wifi_clave" class="qr-ctrl-input" placeholder="Contraseña" />
                  </template>
                </div>

                <div class="qr-ctrl-actions">
                  <div class="qr-quality-row">
                    <span class="qr-ctrl-label">Calidad</span>
                    <div class="qr-quality-btns">
                      <button :class="['qr-q-btn', { active: escalaDescarga === 2 }]" @click="escalaDescarga = 2">Normal</button>
                      <button :class="['qr-q-btn', { active: escalaDescarga === 3 }]" @click="escalaDescarga = 3">Alta</button>
                    </div>
                  </div>
                  <button @click="descargarCard" class="btn-dl-card" :style="formRest.tema === 'oscuro' ? { background: '#1e1e48', color: '#f0c040', border: '1.5px solid #f0c040' } : { background: formRest.tema === 'oscuro' ? '#2a2a6a' : temaActualData.accent }" :disabled="!qrDataUrl">
                    ⬇ Descargar tarjeta (PNG)
                  </button>
                  <a v-if="qrDataUrl" :href="qrDataUrl" :download="`qr-menu-${restaurante?.slug || 'menu'}.png`" class="btn-dl-solo">
                    ⬇ Solo QR (PNG)
                  </a>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- Guardar cambios -->
        <div style="padding: 0 0 24px;">
          <button @click="guardarRestaurante" class="btn-primary" :disabled="guardando">
            {{ guardando ? 'Guardando...' : 'Guardar cambios' }}
          </button>
        </div>
      </div>

      <!-- ════════════════════════════════
           TAB: NEGOCIO
      ════════════════════════════════ -->
      <div v-show="tabActivo === 'negocio'" class="tab-content">
        <!-- Compartir menú -->
        <div class="card">
          <div class="card-header"><h2>Compartir menú</h2></div>
          <div class="card-body">
            <p class="helper-text">Escribe el mensaje introductorio. El nombre del restaurante y el enlace se adjuntan automáticamente.</p>
            <textarea
              v-model="formRest.compartir_mensaje"
              class="compartir-textarea"
              rows="2"
              placeholder="¡Hola! Te comparto el menú digital de"
            ></textarea>
            <p class="compartir-hint">Se adjuntará: <strong>{{ restaurante?.nombre }}</strong> · {{ menuUrl }}</p>
            <div class="compartir-actions">
              <a :href="'https://wa.me/?text=' + encodeURIComponent(textoCompartir)"
                 target="_blank" rel="noopener" class="btn-wa">
                📲 Compartir por WhatsApp
              </a>
              <button @click="copiarUrl" class="btn-copy-link">
                {{ copiado ? '✓ Copiado' : '🔗 Copiar' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Sistema de pedidos -->
        <div class="card">
          <div class="card-header"><h2>Sistema de pedidos</h2></div>
          <div class="card-body">
            <div class="negocio-toggle-row">
              <div>
                <strong>Activar pedidos en el menú</strong>
                <p class="helper-text" style="margin:4px 0 0">Los clientes podrán agregar platillos al carrito y enviarte el pedido por WhatsApp.</p>
              </div>
              <label class="sw">
                <input type="checkbox" v-model="formRest.pedidos_activos" />
                <span class="sw-track" :style="formRest.pedidos_activos ? { background: formRest.tema === 'oscuro' ? '#2a2a6a' : temaActualData.accent } : {}"></span>
              </label>
            </div>
            <template v-if="formRest.pedidos_activos">
              <hr class="negocio-divider" />
              <div class="field" style="max-width:340px; margin-bottom:16px">
                <label>Número de WhatsApp del restaurante</label>
                <input v-model="formRest.pedidos_whatsapp" placeholder="Ej: 521XXXXXXXXXX (con código de país)" />
                <span class="field-hint">Los clientes enviarán su pedido a este número.</span>
              </div>
              <div class="negocio-toggle-row">
                <strong>Ofrecer envío a domicilio</strong>
                <label class="sw">
                  <input type="checkbox" v-model="formRest.pedidos_envio_activo" />
                  <span class="sw-track" :style="formRest.pedidos_envio_activo ? { background: formRest.tema === 'oscuro' ? '#2a2a6a' : temaActualData.accent } : {}"></span>
                </label>
              </div>
              <div v-if="formRest.pedidos_envio_activo" class="field" style="max-width:200px; margin-top:10px">
                <label>Costo del envío ($)</label>
                <input v-model="formRest.pedidos_envio_costo" type="number" min="0" step="0.50" placeholder="0.00" />
              </div>
            </template>
          </div>
        </div>

        <!-- Datos de transferencia -->
        <div v-if="formRest.pedidos_activos" class="card">
          <div class="card-header"><h2>Datos para transferencia</h2></div>
          <div class="card-body">
            <div class="negocio-toggle-row">
              <div>
                <strong>Aceptar transferencia bancaria</strong>
                <p class="helper-text" style="margin:4px 0 0">Los clientes verán la opción de pagar por transferencia en el checkout.</p>
              </div>
              <label class="sw">
                <input type="checkbox" v-model="formRest.pedidos_trans_activo" />
                <span class="sw-track" :style="formRest.pedidos_trans_activo ? { background: formRest.tema === 'oscuro' ? '#2a2a6a' : temaActualData.accent } : {}"></span>
              </label>
            </div>
            <template v-if="formRest.pedidos_trans_activo">
              <hr class="negocio-divider" />
              <div class="form-grid">
                <div class="field">
                  <label>Banco / Alias</label>
                  <input v-model="formRest.pedidos_trans_banco" placeholder="Ej: BBVA, SPIN, CoDi..." />
                </div>
                <div class="field">
                  <label>Titular de la cuenta</label>
                  <input v-model="formRest.pedidos_trans_titular" placeholder="Nombre completo" />
                </div>
                <div class="field">
                  <label>CLABE interbancaria</label>
                  <input v-model="formRest.pedidos_trans_clabe" placeholder="18 dígitos" maxlength="18" />
                </div>
                <div class="field">
                  <label>Número de cuenta</label>
                  <input v-model="formRest.pedidos_trans_cuenta" placeholder="Número de cuenta" />
                </div>
              </div>
            </template>
          </div>
        </div>

        <div style="padding: 0 0 24px;">
          <button @click="guardarRestaurante" class="btn-primary" :disabled="guardando">
            {{ guardando ? 'Guardando...' : 'Guardar cambios' }}
          </button>
        </div>
      </div>

      <!-- ════════════════════════════════
           TAB: PEDIDOS
      ════════════════════════════════ -->
      <div v-show="tabActivo === 'pedidos'" class="tab-content">
        <div class="card">
          <div class="card-header">
            <h2>Pedidos recibidos</h2>
            <button @click="loadPedidos" class="btn-refresh">↺ Actualizar</button>
          </div>
          <div class="card-body no-pad">
            <div v-if="loadingPedidos" class="loading-inline"><div class="spinner"></div></div>
            <div v-else-if="!pedidos.length" class="empty-state" style="padding:40px">
              <span>🛒</span>
              <p>Sin pedidos todavía.</p>
            </div>
            <div v-else class="pedidos-lista">
              <div v-for="ped in pedidos" :key="ped.id" class="pedido-card">
                <div class="pedido-header">
                  <div class="pedido-id">
                    <strong>#{{ ped.numero_pedido }}</strong>
                    <span class="pedido-hora">{{ formatHora(ped.created_at) }}</span>
                  </div>
                  <span :class="['pedido-status', 'status-' + ped.status]">{{ statusLabel(ped.status) }}</span>
                </div>
                <div class="pedido-body">
                  <div class="pedido-cliente">
                    <span>👤 {{ ped.nombre_cliente }}</span>
                    <span v-if="ped.telefono">📞 {{ ped.telefono }}</span>
                    <span v-if="ped.mesa">🪑 Mesa {{ ped.mesa }}</span>
                  </div>
                  <div class="pedido-entrega">
                    <span class="pedido-tag" :class="ped.tipo_entrega === 'envio' ? 'tag-envio' : 'tag-recoger'">
                      {{ ped.tipo_entrega === 'envio' ? '🛵 Envío a domicilio' : '🏠 Recoger en local' }}
                    </span>
                    <span v-if="ped.tipo_entrega === 'envio' && ped.direccion" class="pedido-dir">{{ ped.direccion }}</span>
                    <span class="pedido-tag tag-pago">{{ ped.metodo_pago === 'transferencia' ? '🏦 Transferencia' : '💵 Efectivo' }}</span>
                    <span v-if="ped.denominacion" class="pedido-denominacion">Con ${{ Number(ped.denominacion).toFixed(0) }}</span>
                  </div>
                  <div class="pedido-items-list">
                    <div v-for="item in ped.items" :key="item.id" class="pedido-item-row">
                      <span class="pedido-item-cant">{{ item.cantidad }}×</span>
                      <span class="pedido-item-nombre">{{ item.nombre_producto }}</span>
                      <span v-if="item.observacion" class="pedido-item-obs">— {{ item.observacion }}</span>
                      <span class="pedido-item-precio">${{ Number(item.subtotal).toFixed(2) }}</span>
                    </div>
                  </div>
                  <div class="pedido-totales">
                    <span v-if="ped.costo_envio > 0">Envío: ${{ Number(ped.costo_envio).toFixed(2) }}</span>
                    <strong>Total: ${{ Number(ped.total).toFixed(2) }}</strong>
                  </div>
                </div>
                <div class="pedido-acciones">
                  <button v-if="ped.status === 'nuevo'" @click="cambiarStatus(ped.id, 'visto')" class="btn-status btn-visto">Visto</button>
                  <button v-if="ped.status === 'visto'" @click="cambiarStatus(ped.id, 'en_preparacion')" class="btn-status btn-prep">En preparación</button>
                  <button v-if="ped.status === 'en_preparacion'" @click="cambiarStatus(ped.id, 'listo')" class="btn-status btn-listo">Listo ✓</button>
                  <button v-if="ped.status === 'listo'" @click="cambiarStatus(ped.id, 'entregado')" class="btn-status btn-entregado">Entregado ✓</button>
                  <button v-if="!['entregado','cancelado'].includes(ped.status)" @click="cambiarStatus(ped.id, 'cancelado')" class="btn-status btn-cancelar">Cancelar</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- ═══ Modal preview de foto ═══ -->
    <div v-if="preview" class="preview-overlay" @click="preview = null">
      <div class="preview-box">
        <button class="preview-close" @click="preview = null">✕</button>
        <img :src="preview.url" :alt="preview.nombre" class="preview-img" />
        <p class="preview-nombre">{{ preview.nombre }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '../../composables/useApi.js'
import { ucfirst } from '../../utils/ucfirst.js'
import QRCode from 'qrcode'
import html2canvas from 'html2canvas'

const router = useRouter()
const { get, post, put, del } = useApi()

// ── Estado general ──
const restaurante     = ref(null)
const restauranteId   = ref(null)
const categorias      = ref([])
const productos       = ref([])
const cargandoInicial = ref(true)
const errorInicial    = ref(null)
const loadingProductos = ref(false)
const guardando       = ref(false)
const notif           = ref(null)
const tabActivo       = ref('platillos')
const formAbierto     = ref(true)

// ── Preview de foto ──
const preview = ref(null)

// ── Emoji picker ──
const pickerAbierto = ref(null)

const emojiGrupos = [
  { nombre: 'Platos', emojis: ['🍕','🍔','🌮','🌯','🥗','🍖','🥩','🍗','🍱','🍜','🍝','🍲','🥘','🌭','🥙','🍛'] },
  { nombre: 'Mariscos', emojis: ['🍣','🦐','🦞','🦀','🐟','🍤','🦑','🍙','🦪','🐠'] },
  { nombre: 'Bebidas', emojis: ['🥤','☕','🧃','🍵','🍺','🍷','🍹','🥛','🍸','🧉'] },
  { nombre: 'Postres', emojis: ['🍰','🎂','🍮','🍦','🧁','🍩','🍪','🍫','🍬','🍭','🥧','🍡'] },
  { nombre: 'Extras', emojis: ['⭐','🔥','💎','🌿','🥇','❤️','🌶️','🥑','✨','🆕','🎯'] },
]

const togglePicker = (id) => {
  pickerAbierto.value = pickerAbierto.value === id ? null : id
}

const seleccionarEmoji = (emoji, target) => {
  if (target === 'nuevo') formCat.value.icono = emoji
  else formCatEdit.value.icono = emoji
  pickerAbierto.value = null
}

const cerrarPickerGlobal = (e) => {
  if (!e.target.closest('.emoji-wrap')) pickerAbierto.value = null
}

// ── Edición ──
const prodEditando = ref(null)
const formEdit     = ref({})
const catEditando  = ref(null)
const formCatEdit  = ref({})

// ── QR ──
const qrCardDmEl     = ref(null)
const escalaDescarga = ref(2)
const qrDataUrl  = ref(null)
const qrGenerado = ref(false)
const copiado    = ref(false)

// ── Logo ──
const logoSubiendo = ref(false)

const TEMAS_EXTRA = {
  calido:  { decoColor: 'rgba(255,255,255,0.15)', canvasH1: '#b5451b', canvasH2: '#e8841f' },
  oscuro:  { decoColor: 'rgba(240,192,64,0.1)',   canvasH1: '#0a0a1a', canvasH2: '#1a1a38' },
  moderno: { decoColor: 'rgba(26,127,90,0.08)',   canvasH1: '#f0f8f4', canvasH2: '#e0f5ec' },
  rapida:  { decoColor: 'rgba(255,255,255,0.15)', canvasH1: '#c0392b', canvasH2: '#e74c3c' },
  rosa:    { decoColor: 'rgba(255,255,255,0.18)', canvasH1: '#FF8276', canvasH2: '#EA9087' },
}

const temaActualData = computed(() => {
  const t = temas.find(t => t.id === formRest.value.tema) || temas[0]
  return { ...t, ...(TEMAS_EXTRA[t.id] || TEMAS_EXTRA.calido) }
})

const menuUrl = computed(() => {
  if (!restaurante.value?.slug) return ''
  const origin = import.meta.env.VITE_PUBLIC_ORIGIN || window.location.origin
  const base = import.meta.env.BASE_URL
  return `${origin}${base}?r=${restaurante.value.slug}`
})

// Tabs
const tabs = [
  { id: 'platillos',  icon: '🍽️', label: 'Platillos'  },
  { id: 'categorias', icon: '📋', label: 'Categorías' },
  { id: 'apariencia', icon: '🎨', label: 'Apariencia' },
  { id: 'negocio',    icon: '⚙️', label: 'Negocio'    },
  { id: 'pedidos',    icon: '🛒', label: 'Pedidos'    },
]

// Temas
const temas = [
  { id: 'calido',  nombre: 'Cálido',   desc: 'Bistró, tacos, casero',  bg: '#fdf6f0', cardBg: '#fff',    text: '#3d2c1e', accent: '#d4691e', headerBg: 'linear-gradient(135deg,#b5451b,#e8841f)', headerText: '#fff' },
  { id: 'oscuro',  nombre: 'Oscuro',   desc: 'Bar, premium, elegante', bg: '#1a1a2e', cardBg: '#1e1e2e', text: '#e8e8f0', accent: '#f0c040', headerBg: 'linear-gradient(135deg,#0a0a1a,#1a1a38)', headerText: '#f0c040' },
  { id: 'moderno', nombre: 'Moderno',  desc: 'Saludable, minimalista', bg: '#f4f4f4', cardBg: '#fff',    text: '#111111', accent: '#1a7f5a', headerBg: '#fff',                                   headerText: '#111' },
  { id: 'rapida',  nombre: 'Express',  desc: 'Rápido, cafetería',      bg: '#fffbf0', cardBg: '#fff',    text: '#1a1a1a', accent: '#d43f2e', headerBg: 'linear-gradient(135deg,#c0392b,#e74c3c)', headerText: '#fff' },
  { id: 'rosa',    nombre: 'Rosa',     desc: 'Romántico, suave',       bg: '#FFEFEF', cardBg: '#fff',    text: '#5a2030', accent: '#FF8276', headerBg: 'linear-gradient(135deg,#FF8276,#EA9087)', headerText: '#fff' },
]

// Formularios
const formProd = ref({ categoria_id: '', nombre: '', precio: '', descripcion: '' })
const formCat  = ref({ nombre: '', icono: '' })
const formRest = ref({ nombre: '', descripcion: '', tema: 'calido', qr_frase: 'Delicioso desde el primer vistazo', qr_frase_activa: true, qr_wifi_nombre: '', qr_wifi_clave: '', qr_wifi_activo: false, pedidos_activos: false, pedidos_envio_activo: true, pedidos_envio_costo: 0, pedidos_whatsapp: '', pedidos_trans_activo: false, pedidos_trans_clabe: '', pedidos_trans_cuenta: '', pedidos_trans_titular: '', pedidos_trans_banco: '', compartir_mensaje: '' })

const textoCompartir = computed(() =>
  `${formRest.value.compartir_mensaje}\n${restaurante.value?.nombre || ''}\n${menuUrl.value}`
)

// ── Pedidos ──
const pedidos        = ref([])
const loadingPedidos = ref(false)
let   pedidosInterval = null

const formatHora = (ts) => {
  const d = new Date(ts)
  return d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' }) + ' · ' + d.toLocaleDateString('es-MX', { day: 'numeric', month: 'short' })
}

const statusLabel = (s) => ({ nuevo: 'Nuevo', visto: 'Visto', en_preparacion: 'En preparación', listo: 'Listo', entregado: 'Entregado', cancelado: 'Cancelado' })[s] || s

// Mapas y cómputos
const catMap = computed(() => {
  const m = {}
  categorias.value.forEach(c => { m[c.id] = c.nombre })
  return m
})

const productosOrdenados = computed(() =>
  [...productos.value].sort((a, b) => {
    const ca = catMap.value[a.categoria_id] || ''
    const cb = catMap.value[b.categoria_id] || ''
    return ca.localeCompare(cb) || a.nombre.localeCompare(b.nombre)
  })
)

const conteoProductos = (catId) => productos.value.filter(p => p.categoria_id == catId).length

const thumbUrl = (ruta) => ruta || null

// Notificación temporal
const mostrarNotif = (texto, tipo = 'ok') => {
  notif.value = { texto, tipo }
  setTimeout(() => { notif.value = null }, 3000)
}

const logout = () => {
  localStorage.removeItem('admin_token')
  router.push('/admin')
}

// ── Generar QR ──
const generarQR = async () => {
  if (!menuUrl.value) return
  try {
    qrDataUrl.value = await QRCode.toDataURL(menuUrl.value, {
      width: 300,
      margin: 2,
      color: { dark: '#1a1a1a', light: '#ffffff' },
    })
    qrGenerado.value = true
  } catch {}
}

const descargarCard = async () => {
  if (!qrCardDmEl.value || !qrDataUrl.value) return
  const canvas = await html2canvas(qrCardDmEl.value, {
    scale: escalaDescarga.value,
    useCORS: true,
    backgroundColor: null,
    logging: false,
  })
  const link = document.createElement('a')
  link.download = `tarjeta-qr-${restaurante.value?.slug || 'menu'}.png`
  link.href = canvas.toDataURL('image/png')
  link.click()
}

const copiarUrl = async () => {
  await navigator.clipboard.writeText(textoCompartir.value)
  copiado.value = true
  setTimeout(() => { copiado.value = false }, 2000)
}

const uploadLogo = async (event) => {
  const file = event.target.files?.[0]
  if (!file) return
  logoSubiendo.value = true
  try {
    const fd = new FormData()
    fd.append('logo', file)
    fd.append('restaurante_id', restauranteId.value)
    const token = localStorage.getItem('admin_token')
    const apiBase = import.meta.env.BASE_URL + 'api/'
    const res = await fetch(`${apiBase}?route=upload-logo&token=${token}`, { method: 'POST', body: fd })
    const data = await res.json()
    if (!res.ok) throw new Error(data.error || 'Error al subir el logo')
    restaurante.value = { ...restaurante.value, logo_url: data.logo_url }
    mostrarNotif('Logo actualizado')
  } catch (err) {
    mostrarNotif(err.message, 'error')
  } finally {
    logoSubiendo.value = false
    event.target.value = ''
  }
}

// Regenerar QR y cargar pedidos según el tab activo
watch(tabActivo, (tab) => {
  clearInterval(pedidosInterval)
  if (tab === 'apariencia') setTimeout(generarQR, 100)
  if (tab === 'pedidos') {
    loadPedidos()
    pedidosInterval = setInterval(loadPedidos, 30000)
  }
})

// ── Preview foto ──
const abrirPreview = (prod) => {
  if (!prod.foto_principal) return
  preview.value = { url: thumbUrl(prod.foto_principal), nombre: prod.nombre }
}

// ── Edición de productos ──
const iniciarEdicion = (prod) => {
  prodEditando.value = prod.id
  formEdit.value = {
    categoria_id: prod.categoria_id,
    nombre: prod.nombre,
    precio: prod.precio,
    descripcion: prod.descripcion || '',
  }
}

const cancelarEdicion = () => {
  prodEditando.value = null
  formEdit.value = {}
}

const guardarEdicionProducto = async (id) => {
  if (!formEdit.value.nombre?.trim()) {
    mostrarNotif('El nombre es requerido', 'error')
    return
  }
  guardando.value = true
  try {
    await put('productos', {
      categoria_id: formEdit.value.categoria_id,
      nombre: formEdit.value.nombre.trim(),
      precio: parseFloat(formEdit.value.precio),
      descripcion: formEdit.value.descripcion.trim(),
    }, { id })
    prodEditando.value = null
    await loadProductos()
    mostrarNotif('Platillo actualizado')
  } catch (err) {
    mostrarNotif(err.message, 'error')
  } finally {
    guardando.value = false
  }
}

// ── Edición de categorías ──
const iniciarEdicionCategoria = (cat) => {
  catEditando.value = cat.id
  formCatEdit.value = { nombre: cat.nombre, icono: cat.icono || '' }
}

const guardarEdicionCategoria = async (id) => {
  if (!formCatEdit.value.nombre?.trim()) {
    mostrarNotif('El nombre es requerido', 'error')
    return
  }
  try {
    await put('categorias', {
      nombre: formCatEdit.value.nombre.trim(),
      icono: formCatEdit.value.icono.trim() || null,
    }, { id })
    catEditando.value = null
    await loadCategorias()
    mostrarNotif('Categoría actualizada')
  } catch (err) {
    mostrarNotif(err.message, 'error')
  }
}

const moverCategoria = async (idx, dir) => {
  const arr = [...categorias.value]
  const newIdx = idx + dir
  if (newIdx < 0 || newIdx >= arr.length) return
  ;[arr[idx], arr[newIdx]] = [arr[newIdx], arr[idx]]
  arr.forEach((c, i) => { c.orden = i })
  categorias.value = arr
  try {
    await Promise.all([
      put('categorias', { orden: arr[idx].orden }, { id: arr[idx].id }),
      put('categorias', { orden: arr[newIdx].orden }, { id: arr[newIdx].id }),
    ])
  } catch (err) {
    mostrarNotif(err.message, 'error')
    await loadCategorias()
  }
}

// ── Carga inicial ──
onMounted(async () => {
  document.addEventListener('click', cerrarPickerGlobal)
  try {
    const res = await get('restaurantes')
    const lista = res.restaurantes || []
    if (!lista.length) {
      errorInicial.value = 'No hay restaurante configurado.'
      return
    }
    const rest = lista[0]
    restaurante.value = rest
    restauranteId.value = rest.id
    formRest.value = {
      nombre: rest.nombre || '',
      descripcion: rest.descripcion || '',
      tema: rest.tema || 'calido',
      qr_frase: rest.qr_frase || 'Delicioso desde el primer vistazo',
      qr_frase_activa: Boolean(rest.qr_frase_activa ?? true),
      qr_wifi_nombre: rest.qr_wifi_nombre || '',
      qr_wifi_clave: rest.qr_wifi_clave || '',
      qr_wifi_activo: Boolean(rest.qr_wifi_activo ?? false),
      pedidos_activos: Boolean(rest.pedidos_activos ?? false),
      pedidos_envio_activo: Boolean(rest.pedidos_envio_activo ?? true),
      pedidos_envio_costo: parseFloat(rest.pedidos_envio_costo) || 0,
      pedidos_whatsapp: rest.pedidos_whatsapp || '',
      pedidos_trans_activo: Boolean(rest.pedidos_trans_activo ?? false),
      pedidos_trans_clabe: rest.pedidos_trans_clabe || '',
      pedidos_trans_cuenta: rest.pedidos_trans_cuenta || '',
      pedidos_trans_titular: rest.pedidos_trans_titular || '',
      pedidos_trans_banco: rest.pedidos_trans_banco || '',
      compartir_mensaje: rest.compartir_mensaje || '¡Hola! Te comparto el menú digital de',
    }
    await Promise.all([loadCategorias(), loadProductos()])
  } catch (err) {
    errorInicial.value = 'Error al conectar: ' + err.message
  } finally {
    cargandoInicial.value = false
  }
})

onUnmounted(() => {
  document.removeEventListener('click', cerrarPickerGlobal)
  clearInterval(pedidosInterval)
})

// ── Favicon dinámico ──
watch(() => restaurante.value?.logo_url, (url) => {
  if (!url) return
  let link = document.querySelector("link[rel~='icon']")
  if (!link) {
    link = document.createElement('link')
    link.rel = 'icon'
    document.head.appendChild(link)
  }
  link.href = url
}, { immediate: true })

// ── Categorías ──
async function loadCategorias() {
  const res = await get('categorias', { restaurante_id: restauranteId.value })
  categorias.value = res.categorias || []
}

async function crearCategoria() {
  if (!formCat.value.nombre.trim()) { mostrarNotif('Escribe un nombre', 'error'); return }
  try {
    await post('categorias', { restaurante_id: restauranteId.value, nombre: formCat.value.nombre.trim(), icono: formCat.value.icono.trim() || null })
    formCat.value = { nombre: '', icono: '' }
    await loadCategorias()
    mostrarNotif('Categoría creada')
  } catch (err) { mostrarNotif(err.message, 'error') }
}

async function eliminarCategoria(id) {
  if (!confirm('¿Eliminar esta categoría?')) return
  try {
    await del('categorias', { id })
    await loadCategorias()
    mostrarNotif('Categoría eliminada')
  } catch (err) { mostrarNotif(err.message, 'error') }
}

// ── Productos ──
async function loadProductos() {
  loadingProductos.value = true
  try {
    const res = await get('productos', { restaurante_id: restauranteId.value })
    productos.value = res.productos || []
  } finally {
    loadingProductos.value = false
  }
}

async function crearProducto() {
  const f = formProd.value
  if (!f.categoria_id || !f.nombre.trim() || f.precio === '') { mostrarNotif('Categoría, nombre y precio son requeridos', 'error'); return }
  guardando.value = true
  try {
    await post('productos', { categoria_id: f.categoria_id, nombre: f.nombre.trim(), precio: parseFloat(f.precio), descripcion: f.descripcion.trim() })
    formProd.value = { categoria_id: '', nombre: '', precio: '', descripcion: '' }
    await loadProductos()
    mostrarNotif('Platillo agregado')
  } catch (err) { mostrarNotif(err.message, 'error') }
  finally { guardando.value = false }
}

async function eliminarProducto(id) {
  if (!confirm('¿Eliminar este platillo del menú?')) return
  try {
    await del('productos', { id })
    await loadProductos()
    mostrarNotif('Platillo eliminado')
  } catch (err) { mostrarNotif(err.message, 'error') }
}

async function subirFotos(prodId, event) {
  const files = event.target.files
  if (!files.length) return
  const fd = new FormData()
  fd.append('producto_id', prodId)
  for (let i = 0; i < files.length; i++) fd.append('fotos[]', files[i])
  try {
    const token = localStorage.getItem('admin_token')
    const res = await fetch(`${import.meta.env.BASE_URL}api/?route=upload-fotos&token=${token}`, { method: 'POST', body: fd })
    if (!res.ok) throw new Error('Error al subir fotos')
    event.target.value = ''
    await loadProductos()
    mostrarNotif('Foto subida correctamente')
  } catch (err) { mostrarNotif(err.message, 'error') }
}

async function subirGlb(prodId, event) {
  const file = event.target.files[0]
  if (!file) return
  const fd = new FormData()
  fd.append('producto_id', prodId)
  fd.append('modelo', file)
  try {
    const token = localStorage.getItem('admin_token')
    const res = await fetch(`${import.meta.env.BASE_URL}api/?route=upload-glb&token=${token}`, { method: 'POST', body: fd })
    const data = await res.json()
    if (!res.ok) throw new Error(data.error || 'Error al subir el modelo')
    event.target.value = ''
    await loadProductos()
    mostrarNotif('Modelo 3D subido. ¡Ya disponible en el menú!')
  } catch (err) { mostrarNotif(err.message, 'error') }
}

// ── Pedidos ──
async function loadPedidos() {
  loadingPedidos.value = true
  try {
    const res = await get('pedidos', { restaurante_id: restauranteId.value })
    pedidos.value = res.pedidos || []
  } finally {
    loadingPedidos.value = false
  }
}

async function cambiarStatus(id, status) {
  try {
    await put('pedidos', { status }, { id })
    await loadPedidos()
    mostrarNotif('Pedido actualizado')
  } catch (err) {
    mostrarNotif(err.message, 'error')
  }
}

// ── Restaurante ──
async function guardarRestaurante() {
  guardando.value = true
  try {
    await put('restaurantes', formRest.value, { id: restauranteId.value })
    restaurante.value = { ...restaurante.value, ...formRest.value }
    mostrarNotif('Cambios guardados')
  } catch (err) { mostrarNotif(err.message, 'error') }
  finally { guardando.value = false }
}
</script>

<style scoped>
/* ─── Base ─── */
.admin-panel {
  --accent: #FF6B35;
  min-height: 100vh;
  background: #f0f2f5;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* ─── Header ─── */
.panel-header {
  background: #fff;
  border-bottom: 1px solid #e8e8e8;
  padding: 0 24px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}
.header-left { display: flex; align-items: center; gap: 12px; }
.header-icon { font-size: 1.8rem; }
.header-logo-img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #f0f0f0; }
.header-title { font-size: 1.1rem; font-weight: 700; color: #1a1a1a; margin: 0; }
.header-sub { font-size: 0.75rem; color: #aaa; }
.btn-logout {
  background: transparent; border: 1.5px solid #ddd; color: #777;
  padding: 7px 16px; border-radius: 8px; font-size: 0.85rem;
  font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.btn-logout:hover { border-color: #e53935; color: #e53935; }

/* ─── Loading / Error ─── */
.loading-screen, .error-screen {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; min-height: 60vh; gap: 16px; color: #888;
}
.spinner-lg {
  width: 44px; height: 44px;
  border: 3px solid #e0e0e0; border-top-color: #FF6B35;
  border-radius: 50%; animation: spin 0.7s linear infinite;
}
.spinner {
  width: 28px; height: 28px;
  border: 2.5px solid #e0e0e0; border-top-color: #FF6B35;
  border-radius: 50%; animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ─── Body ─── */
.panel-body { max-width: 900px; margin: 0 auto; padding: 20px 16px 60px; }

/* ─── Tabs ─── */
.tab-nav {
  display: flex; gap: 4px; background: #fff; border-radius: 14px;
  padding: 6px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); margin-bottom: 20px;
}
.tab-btn {
  flex: 1; display: flex; align-items: center; justify-content: center;
  gap: 7px; padding: 10px; border: none; border-radius: 10px;
  background: transparent; color: #999; font-size: 0.88rem;
  font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.tab-btn:hover { background: #f5f5f5; color: #333; }
.tab-btn.active { background: var(--accent); color: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
.tab-icon { font-size: 1.05rem; }

/* ─── Cards ─── */
.card { background: #fff; border-radius: 14px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); margin-bottom: 16px; }
.card-header {
  padding: 16px 20px; border-bottom: 1px solid #f0f0f0;
  display: flex; align-items: center; justify-content: space-between;
}
.card-header.collapsible { cursor: pointer; user-select: none; }
.card-header h2 { font-size: 0.95rem; font-weight: 700; color: #1a1a1a; margin: 0; }
.chevron { font-size: 0.8rem; color: #bbb; }
.count-badge { background: #f0f0f0; color: #666; border-radius: 20px; padding: 2px 10px; font-size: 0.8rem; font-weight: 700; }
.card-body { padding: 20px; }
.card-body.no-pad { padding: 0; }

/* ─── Formularios ─── */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-row { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
.field { display: flex; flex-direction: column; gap: 5px; }
.field-full { grid-column: 1 / -1; }
.field-btn { flex-shrink: 0; }
.field label { font-size: 0.8rem; font-weight: 600; color: #555; }
.field input, .field select, .field textarea {
  padding: 10px 12px; border: 1.5px solid #e0e0e0; border-radius: 8px;
  font-size: 0.9rem; outline: none; background: #fafafa; transition: border-color 0.2s; font-family: inherit;
}
.field input:focus, .field select:focus, .field textarea:focus { border-color: var(--accent); background: #fff; }
.field textarea { resize: vertical; min-height: 60px; }

/* ─── Botones ─── */
.btn-primary {
  background: var(--accent);
  color: #fff; border: none; padding: 10px 20px; border-radius: 8px;
  font-size: 0.9rem; font-weight: 700; cursor: pointer;
  transition: opacity 0.2s, transform 0.1s; white-space: nowrap;
}
.btn-primary:hover:not(:disabled) { opacity: 0.88; transform: translateY(-1px); }
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

/* Tema oscuro: botones con fondo navy + borde/texto dorado */
.tema-oscuro-admin .btn-primary {
  background: #1e1e48;
  color: #f0c040;
  border: 1.5px solid #f0c040;
}
.tema-oscuro-admin .btn-primary:hover:not(:disabled) {
  background: rgba(240,192,64,0.15);
  opacity: 1;
}
.tema-oscuro-admin .tab-btn.active {
  background: #1e1e48;
  color: #f0c040;
  border-color: #f0c040;
  box-shadow: 0 2px 8px rgba(240,192,64,0.2);
}

.btn-icon {
  width: 34px; height: 34px; display: flex; align-items: center;
  justify-content: center; border: none; border-radius: 8px;
  cursor: pointer; font-size: 0.9rem; transition: background 0.2s; flex-shrink: 0;
}
label.btn-icon { cursor: pointer; }
.btn-edit  { background: #fff3e0; }
.btn-edit:hover  { background: #ffe0b2; }
.btn-foto  { background: #e3f2fd; }
.btn-foto:hover  { background: #bbdefb; }
.btn-3d    { background: #f3e5f5; }
.btn-3d:hover    { background: #e1bee7; }
.btn-del   { background: #ffebee; color: #c62828; }
.btn-del:hover   { background: #ffcdd2; }

.btn-save  { background: #2e7d32; color: #fff; border: none; padding: 7px 14px; border-radius: 7px; font-size: 0.85rem; font-weight: 700; cursor: pointer; }
.btn-save:hover  { background: #1b5e20; }
.btn-cancel { background: #f5f5f5; color: #555; border: none; padding: 7px 14px; border-radius: 7px; font-size: 0.85rem; font-weight: 600; cursor: pointer; }
.btn-cancel:hover { background: #e0e0e0; }

.btn-save-sm   { background: #2e7d32; color: #fff; border: none; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; }
.btn-cancel-sm { background: #f5f5f5; color: #555; border: none; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; }

/* ─── Lista de productos ─── */
.loading-inline { display: flex; justify-content: center; padding: 32px; }
.empty-state {
  text-align: center; color: #bbb;
  display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 40px;
}
.empty-state span { font-size: 2.5rem; }
.empty-state p { font-size: 0.9rem; line-height: 1.5; }

.prod-lista { display: flex; flex-direction: column; }

.prod-item {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 16px; border-bottom: 1px solid #f5f5f5; transition: background 0.15s;
}
.prod-item:last-child { border-bottom: none; }
.prod-item:hover { background: #fafafa; }
.prod-item.editing { background: #fffde7; align-items: flex-start; }

/* ── Miniatura ── */
.prod-thumb {
  width: 56px; height: 56px; border-radius: 8px; background: #f0f0f0;
  overflow: hidden; flex-shrink: 0; cursor: pointer; position: relative;
}
.prod-thumb img { width: 100%; height: 100%; object-fit: cover; }
.thumb-empty { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; opacity: 0.35; }
.thumb-overlay {
  position: absolute; inset: 0; background: rgba(0,0,0,0.45);
  display: flex; align-items: center; justify-content: center;
  opacity: 0; transition: opacity 0.2s; font-size: 1.2rem;
}
.prod-thumb:hover .thumb-overlay { opacity: 1; }

/* ── Info platillo ── */
.prod-info { flex: 1; display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.prod-nombre { font-size: 0.92rem; font-weight: 700; color: #1a1a1a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.prod-cat  { font-size: 0.75rem; color: #aaa; }
.prod-precio { font-size: 0.88rem; font-weight: 700; color: var(--accent); }

.prod-badges { flex-shrink: 0; }
.badge { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; }
.badge-3d   { background: #e8f5e9; color: #2e7d32; }
.badge-no3d { background: #f5f5f5; color: #bbb; }

.prod-actions { display: flex; gap: 5px; flex-shrink: 0; }

/* ── Formulario edición inline ── */
.prod-edit-form { display: flex; gap: 12px; width: 100%; padding: 4px 0; }
.edit-thumb {
  width: 56px; height: 56px; border-radius: 8px; background: #f0f0f0;
  overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
}
.edit-thumb img { width: 100%; height: 100%; object-fit: cover; }
.edit-fields { flex: 1; display: flex; flex-direction: column; gap: 7px; }
.edit-fields select,
.edit-fields input,
.edit-fields textarea {
  padding: 7px 10px; border: 1.5px solid #e0e0e0; border-radius: 7px;
  font-size: 0.88rem; outline: none; font-family: inherit; background: #fff;
}
.edit-fields select:focus,
.edit-fields input:focus,
.edit-fields textarea:focus { border-color: var(--accent); }
.edit-fields textarea { resize: vertical; min-height: 50px; }
.edit-actions { display: flex; gap: 8px; }

/* ─── Categorías ─── */
.cat-lista { display: flex; flex-direction: column; }
.cat-item {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 16px; border-bottom: 1px solid #f5f5f5;
}
.cat-item:last-child { border-bottom: none; }
.cat-emoji  { font-size: 1.3rem; width: 28px; text-align: center; flex-shrink: 0; }
.cat-nombre { flex: 1; font-weight: 600; font-size: 0.9rem; color: #333; }
.cat-count  { font-size: 0.78rem; color: #bbb; flex-shrink: 0; }
.cat-ord-btns { display: flex; flex-direction: column; gap: 2px; flex-shrink: 0; }
.btn-ord { width: 22px; height: 18px; border: 1px solid #e0e0e0; border-radius: 4px; background: #fafafa; font-size: 0.6rem; cursor: pointer; line-height: 1; padding: 0; }
.btn-ord:hover:not(:disabled) { background: #eee; }
.btn-ord:disabled { opacity: 0.25; cursor: default; }

/* ── Edición categoría inline ── */
.cat-edit-form { display: flex; align-items: center; gap: 8px; width: 100%; }
.input-nombre { flex: 1; padding: 7px 10px; border: 1.5px solid #e0e0e0; border-radius: 7px; font-size: 0.9rem; outline: none; }
.input-nombre:focus { border-color: var(--accent); }

/* ─── Logo upload ─── */
.logo-upload-row { display: flex; align-items: center; gap: 20px; }
.logo-preview-wrap { width: 80px; height: 80px; border-radius: 12px; overflow: hidden; border: 1px solid #e0e0e0; background: #f8f8f8; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
.logo-preview-img { width: 100%; height: 100%; object-fit: contain; }
.logo-preview-empty { font-size: 2rem; }
.logo-upload-actions { display: flex; flex-direction: column; gap: 6px; }
.btn-upload-logo {
  display: inline-block; padding: 8px 16px; background: #f0f0f0; border: 1.5px solid #ddd;
  border-radius: 8px; font-size: 0.88rem; font-weight: 600; color: #333;
  cursor: pointer; transition: background 0.15s; user-select: none;
}
.btn-upload-logo:hover { background: #e4e4e4; }
.btn-upload-logo.loading { opacity: 0.6; cursor: not-allowed; }
.logo-hint { font-size: 0.78rem; color: #aaa; }

/* ─── Temas ─── */
.helper-text { font-size: 0.85rem; color: #999; margin-bottom: 14px; }
.temas-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; }
.tema-card {
  border: 2px solid #e0e0e0; border-radius: 12px; overflow: hidden;
  cursor: pointer; transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
}
.tema-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
.tema-card.selected { box-shadow: 0 4px 16px rgba(0,0,0,0.15); }

.tema-mockup { padding: 12px; display: flex; flex-direction: column; gap: 7px; }
.mock-title { font-size: 0.7rem; font-weight: 700; }
.mock-card { border-radius: 6px; padding: 7px; display: flex; gap: 6px; align-items: center; }
.mock-img  { width: 26px; height: 26px; border-radius: 4px; flex-shrink: 0; }
.mock-info { flex: 1; display: flex; flex-direction: column; gap: 4px; }
.mock-name { height: 7px; border-radius: 3px; }
.mock-price { font-size: 0.7rem; font-weight: 800; }

.tema-label {
  padding: 8px 12px; display: flex; flex-direction: column; gap: 2px;
  background: rgba(255,255,255,0.12); border-top: 1px solid rgba(0,0,0,0.05);
}
.tema-label strong { font-size: 0.85rem; }
.tema-label span   { font-size: 0.72rem; opacity: 0.65; }
.tema-activo { color: #2e7d32 !important; font-weight: 700 !important; opacity: 1 !important; }

/* ─── QR Card ─── */
.qr-dashboard-body { display: flex; flex-direction: column; gap: 16px; }
.qr-url-box { display: flex; gap: 8px; align-items: center; background: #f5f5f5; border-radius: 8px; padding: 10px 14px; width: 100%; box-sizing: border-box; }
.qr-url-text { font-size: 0.78rem; color: #555; flex: 1; word-break: break-all; font-family: monospace; }
.btn-copy { background: #fff; border: 1.5px solid #ddd; color: #555; padding: 5px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; cursor: pointer; white-space: nowrap; transition: all 0.2s; flex-shrink: 0; }
.btn-copy:hover { border-color: var(--accent); color: var(--accent); }

.qr-card-layout { display: flex; gap: 28px; align-items: flex-start; flex-wrap: wrap; }

/* Preview col */
.qr-card-preview-col { display: flex; flex-direction: column; align-items: center; gap: 8px; flex-shrink: 0; }
.qr-preview-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #aaa; margin: 0; }

.qr-card-dm { width: 240px; border-radius: 20px; box-shadow: 0 14px 44px rgba(0,0,0,0.16); display: flex; flex-direction: column; overflow: hidden; background: #fff; }

.qr-card-dm-hdr { height: 108px; position: relative; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.qr-hdr-deco { position: absolute; border-radius: 50%; filter: blur(14px); }
.qr-hdr-deco-1 { width: 90px; height: 90px; top: -22px; right: -22px; }
.qr-hdr-deco-2 { width: 72px; height: 72px; bottom: -18px; left: -18px; }
.qr-hdr-inner { position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; gap: 5px; }
.qr-hdr-emoji { font-size: 1.8rem; line-height: 1; }
.qr-hdr-logo { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.4); }
.qr-hdr-nombre { font-size: 0.8rem; font-weight: 700; text-align: center; padding: 0 10px; }

.qr-card-dm-body { flex: 1; display: flex; flex-direction: column; align-items: center; padding: 12px 14px 10px; gap: 8px; text-align: center; }
.qr-dm-title { margin: 0; font-size: 1rem; font-weight: 800; color: #1a1a1a; }
.qr-dm-frase { margin: 0; font-size: 0.68rem; color: #888; font-style: italic; }
.qr-dm-qr-wrap { border-radius: 11px; border: 2px solid; padding: 6px; background: #fff; }
.qr-dm-img { width: 108px; height: 108px; display: block; }
.qr-dm-placeholder { width: 108px; height: 108px; display: flex; align-items: center; justify-content: center; background: #f5f5f5; border-radius: 6px; }
.qr-dm-wifi { display: flex; align-items: center; gap: 6px; font-weight: 600; }
.qr-wifi-texts { display: flex; flex-direction: column; text-align: left; }
.qr-wifi-net  { font-size: 0.68rem; font-weight: 700; }
.qr-wifi-pass { font-size: 0.63rem; opacity: 0.65; }
.qr-card-dm-bar { height: 9px; }

/* Controls col */
.qr-card-controls-col { flex: 1; min-width: 220px; display: flex; flex-direction: column; gap: 14px; }
.qr-ctrl-title { margin: 0; font-size: 1rem; font-weight: 700; color: #222; }
.qr-ctrl-group { background: #f9f9f9; border: 1px solid #eee; border-radius: 10px; padding: 12px; display: flex; flex-direction: column; gap: 8px; }
.qr-ctrl-header { display: flex; align-items: center; justify-content: space-between; }
.qr-ctrl-label { font-size: 0.83rem; font-weight: 600; color: #444; }
.qr-ctrl-input { width: 100%; box-sizing: border-box; padding: 7px 10px; border: 1px solid #e0e0e0; border-radius: 7px; font-size: 0.88rem; outline: none; }
.qr-ctrl-input:focus { border-color: #aaa; }

/* Toggle switch */
.sw { position: relative; display: inline-flex; cursor: pointer; }
.sw input { opacity: 0; width: 0; height: 0; position: absolute; }
.sw-track { width: 38px; height: 20px; background: #ccc; border-radius: 10px; transition: background 0.2s; position: relative; }
.sw-track::after { content: ''; position: absolute; width: 14px; height: 14px; background: #fff; border-radius: 50%; top: 3px; left: 3px; transition: transform 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
.sw input:checked ~ .sw-track::after { transform: translateX(18px); }

.qr-ctrl-actions { display: flex; flex-direction: column; gap: 8px; margin-top: 4px; }
.qr-quality-row { display: flex; align-items: center; justify-content: space-between; }
.qr-quality-btns { display: flex; gap: 6px; }
.qr-q-btn { padding: 5px 14px; border: 1px solid #ddd; border-radius: 6px; background: #f5f5f5; font-size: 0.8rem; cursor: pointer; transition: background 0.15s, color 0.15s; }
.qr-q-btn.active { background: #222; color: #fff; border-color: #222; }
.btn-dl-card { color: #fff; border: none; padding: 11px; border-radius: 9px; font-size: 0.9rem; font-weight: 700; cursor: pointer; transition: opacity 0.15s; }
.btn-dl-card:hover { opacity: 0.88; }
.btn-dl-card:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-dl-solo { display: inline-block; text-align: center; background: #eeeeee; color: #555; text-decoration: none; padding: 9px; border-radius: 9px; font-size: 0.85rem; font-weight: 600; transition: background 0.15s; }
.btn-dl-solo:hover { background: #e0e0e0; }

/* ─── Preview foto ─── */
.preview-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.8);
  display: flex; align-items: center; justify-content: center;
  z-index: 2000; padding: 20px; cursor: pointer;
}
.preview-box {
  position: relative; background: #fff; border-radius: 16px;
  overflow: hidden; max-width: 480px; width: 100%; cursor: default;
}
.preview-close {
  position: absolute; top: 12px; right: 12px; width: 34px; height: 34px;
  border-radius: 50%; background: rgba(0,0,0,0.15); border: none;
  cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; z-index: 1;
}
.preview-img { width: 100%; display: block; max-height: 70vh; object-fit: contain; }
.preview-nombre { padding: 12px 16px; font-weight: 700; font-size: 0.95rem; color: #333; margin: 0; text-align: center; }

/* ─── Notificación ─── */
.notif {
  position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
  padding: 12px 24px; border-radius: 10px; font-size: 0.88rem; font-weight: 600;
  box-shadow: 0 6px 24px rgba(0,0,0,0.2); z-index: 999; white-space: nowrap;
}
.notif-ok    { background: #1b5e20; color: #fff; }
.notif-error { background: #b71c1c; color: #fff; }

.notif-anim-enter-active, .notif-anim-leave-active { transition: opacity 0.3s, transform 0.3s; }
.notif-anim-enter-from { opacity: 0; transform: translateX(-50%) translateY(10px); }
.notif-anim-leave-to   { opacity: 0; transform: translateX(-50%) translateY(10px); }

/* ─── Emoji picker ─── */
.emoji-wrap {
  position: relative;
  display: inline-block;
}

.emoji-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 9px 12px;
  border: 1.5px solid #e0e0e0;
  border-radius: 8px;
  background: #fafafa;
  cursor: pointer;
  transition: border-color 0.2s, background 0.2s;
  white-space: nowrap;
}

.emoji-btn:hover {
  border-color: var(--accent);
  background: #fff;
}

.emoji-btn-sm {
  padding: 6px 10px;
}

.emoji-display { font-size: 1.2rem; line-height: 1; }
.picker-caret  { font-size: 0.65rem; color: #bbb; }

.emoji-picker {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  z-index: 300;
  background: #fff;
  border: 1px solid #e8e8e8;
  border-radius: 14px;
  box-shadow: 0 10px 36px rgba(0,0,0,0.16);
  padding: 14px;
  width: 305px;
  max-height: 360px;
  overflow-y: auto;
  scrollbar-width: thin;
}

/* En el edit inline, abrir hacia la derecha del botón */
.emoji-picker-right {
  left: 0;
  right: auto;
}

.emoji-grupo {
  margin-bottom: 12px;
}
.emoji-grupo:last-child {
  margin-bottom: 0;
}

.emoji-grupo-titulo {
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.7px;
  color: #bbb;
  margin-bottom: 6px;
  padding-left: 2px;
}

.emoji-grid {
  display: grid;
  grid-template-columns: repeat(9, 1fr);
  gap: 1px;
}

.emoji-opt {
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 1.05rem;
  border-radius: 6px;
  transition: background 0.12s, transform 0.12s;
  padding: 0;
}

.emoji-opt:hover {
  background: #f0f0f0;
  transform: scale(1.25);
}

.emoji-opt.selected {
  background: #fff3e0;
  outline: 2px solid var(--accent);
}

/* ─── Responsive ─── */
@media (max-width: 600px) {
  .panel-header { padding: 0 14px; }
  .header-title { font-size: 1rem; }
  .tab-btn .tab-label { display: none; }
  .tab-icon { font-size: 1.3rem; }
  .form-grid { grid-template-columns: 1fr; }
  .temas-grid { grid-template-columns: 1fr 1fr; }
  .prod-badges { display: none; }
}

/* ─── Tab Negocio ─── */
.negocio-toggle-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 4px 0; }
.negocio-divider { border: none; border-top: 1px solid #f0f0f0; margin: 16px 0; }
.field-hint { font-size: 0.75rem; color: #aaa; margin-top: 3px; display: block; }
.compartir-textarea {
  width: 100%; box-sizing: border-box;
  border: 1px solid #ddd; border-radius: 8px;
  padding: 10px 12px; font-size: 0.88rem; line-height: 1.5;
  color: #444; resize: vertical; margin-bottom: 12px;
  font-family: inherit;
}
.compartir-textarea:focus { outline: none; border-color: #aaa; }
.compartir-hint {
  font-size: 0.78rem; color: #aaa; margin: -4px 0 12px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.compartir-hint strong { color: #888; }
.compartir-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.btn-wa {
  display: inline-flex; align-items: center; gap: 8px;
  background: #25D366; color: #fff; text-decoration: none;
  padding: 10px 18px; border-radius: 9px; font-size: 0.9rem; font-weight: 700;
  transition: opacity 0.2s;
}
.btn-wa:hover { opacity: 0.88; }
.btn-copy-link {
  display: inline-flex; align-items: center; gap: 8px;
  background: #f0f0f0; color: #444; border: none;
  padding: 10px 18px; border-radius: 9px; font-size: 0.9rem; font-weight: 700;
  cursor: pointer; transition: background 0.2s;
}
.btn-copy-link:hover { background: #e0e0e0; }
.btn-refresh {
  background: #f5f5f5; border: 1px solid #e0e0e0; color: #555;
  padding: 5px 12px; border-radius: 7px; font-size: 0.82rem;
  font-weight: 600; cursor: pointer; transition: background 0.15s;
}
.btn-refresh:hover { background: #ebebeb; }

/* ─── Tab Pedidos ─── */
.pedidos-lista { display: flex; flex-direction: column; gap: 0; }
.pedido-card { border-bottom: 1px solid #f0f0f0; padding: 16px 20px; }
.pedido-card:last-child { border-bottom: none; }

.pedido-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.pedido-id { display: flex; align-items: center; gap: 10px; }
.pedido-id strong { font-size: 0.95rem; color: #1a1a1a; }
.pedido-hora { font-size: 0.75rem; color: #aaa; }

.pedido-status { padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
.status-nuevo         { background: #ffebee; color: #c62828; }
.status-visto         { background: #fff3e0; color: #e65100; }
.status-en_preparacion{ background: #e3f2fd; color: #1565c0; }
.status-listo         { background: #e8f5e9; color: #2e7d32; }
.status-entregado     { background: #f5f5f5; color: #9e9e9e; }
.status-cancelado     { background: #fce4ec; color: #880e4f; }

.pedido-body { display: flex; flex-direction: column; gap: 8px; }
.pedido-cliente { display: flex; flex-wrap: wrap; gap: 12px; font-size: 0.85rem; color: #555; }
.pedido-entrega { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; font-size: 0.82rem; }
.pedido-tag { padding: 2px 8px; border-radius: 5px; font-weight: 600; background: #f0f0f0; color: #555; }
.tag-envio  { background: #e3f2fd; color: #1565c0; }
.tag-recoger{ background: #f3e5f5; color: #6a1b9a; }
.tag-pago   { background: #e8f5e9; color: #2e7d32; }
.pedido-dir { font-size: 0.8rem; color: #888; }
.pedido-denominacion { font-size: 0.8rem; color: #888; }

.pedido-items-list { background: #fafafa; border-radius: 8px; padding: 10px 12px; display: flex; flex-direction: column; gap: 5px; }
.pedido-item-row { display: flex; align-items: baseline; gap: 6px; font-size: 0.85rem; }
.pedido-item-cant   { font-weight: 700; color: var(--accent); min-width: 24px; }
.pedido-item-nombre { flex: 1; font-weight: 600; color: #1a1a1a; }
.pedido-item-obs    { font-size: 0.78rem; color: #999; font-style: italic; }
.pedido-item-precio { font-weight: 700; color: #555; }

.pedido-totales { display: flex; justify-content: flex-end; gap: 16px; font-size: 0.88rem; color: #888; }
.pedido-totales strong { color: #1a1a1a; font-size: 0.95rem; }

.pedido-acciones { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
.btn-status { padding: 6px 14px; border: none; border-radius: 7px; font-size: 0.82rem; font-weight: 700; cursor: pointer; transition: opacity 0.15s; }
.btn-status:hover { opacity: 0.82; }
.btn-visto     { background: #fff3e0; color: #e65100; }
.btn-prep      { background: #e3f2fd; color: #1565c0; }
.btn-listo     { background: #e8f5e9; color: #2e7d32; }
.btn-entregado { background: #c8e6c9; color: #1b5e20; }
.btn-cancelar  { background: #ffebee; color: #c62828; }
</style>
