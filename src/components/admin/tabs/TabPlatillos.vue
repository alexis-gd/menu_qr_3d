<template>
  <div class="tab-content">
    <!-- Categorías -->
    <div class="card">
      <div class="card-header collapsible" @click="catAbierto = !catAbierto">
        <h2>Categorías</h2>
        <div>
          <span class="count-badge" style="margin-right:8px">{{ categorias.length }}</span>
          <span class="chevron">{{ catAbierto ? '▲' : '▼' }}</span>
        </div>
      </div>
      <div v-show="catAbierto" class="card-body">
        
        <div class="form-row" style="margin-bottom:20px; display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap">
          <div class="field" style="flex:1">
            <label>Nueva categoría</label>
            <input :value="formCat.nombre" @input="formCat.nombre = ucfirst($event.target.value)" placeholder="Ej: Entradas, Bebidas..." @keyup.enter="crearCategoria" />
          </div>
          <div class="field">
            <label>Ícono</label>
            <div class="icono-wrap">
              <button type="button" class="icono-btn" @click.stop="togglePicker('nuevo')">
                <SvgIcon v-if="formCat.icono" :path="resolverIcono(formCat.icono)" :size="20" />
                <SvgIcon v-else :path="mdiFormatListBulleted" :size="20" />
                <span class="picker-caret">▾</span>
              </button>
              <div v-if="pickerAbierto === 'nuevo'" class="icono-picker" @click.stop>
                <div v-for="g in ICONO_GRUPOS" :key="g.nombre" class="icono-grupo">
                  <div class="icono-grupo-titulo">{{ g.nombre }}</div>
                  <div class="icono-grid">
                    <button
                      v-for="ic in g.iconos" :key="ic.key"
                      type="button"
                      class="icono-opt"
                      :class="{ selected: formCat.icono === ic.key }"
                      :title="ic.label"
                      @click="seleccionarIcono(ic.key, 'nuevo')"
                    >
                      <SvgIcon :path="ic.path" :size="18" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="field field-btn" style="flex-shrink:0">
            <label>&nbsp;</label>
            <button @click="crearCategoria" class="btn-primary" :disabled="guardandoCat">
              {{ guardandoCat ? 'Agregando...' : 'Agregar' }}
            </button>
          </div>
        </div>

        <div v-if="!categorias.length" class="empty-state" style="padding:16px">
          <SvgIcon :path="mdiFormatListBulleted" :size="36" />
          <p>Sin categorías todavía.</p>
        </div>
        <div v-else class="cat-lista">
          <div v-for="(cat, idx) in categorias" :key="cat.id" class="cat-item">
            <!-- Modo edición -->
            <div v-if="catEditando === cat.id" class="cat-edit-form">
              <div class="icono-wrap">
                <button type="button" class="icono-btn icono-btn-sm" @click.stop="togglePicker(cat.id)">
                  <SvgIcon v-if="formCatEdit.icono" :path="resolverIcono(formCatEdit.icono)" :size="18" />
                  <SvgIcon v-else :path="mdiFormatListBulleted" :size="18" />
                  <span class="picker-caret">▾</span>
                </button>
                <div v-if="pickerAbierto === cat.id" class="icono-picker icono-picker-right" @click.stop>
                  <div v-for="g in ICONO_GRUPOS" :key="g.nombre" class="icono-grupo">
                    <div class="icono-grupo-titulo">{{ g.nombre }}</div>
                    <div class="icono-grid">
                      <button
                        v-for="ic in g.iconos" :key="ic.key"
                        type="button"
                        class="icono-opt"
                        :class="{ selected: formCatEdit.icono === ic.key }"
                        :title="ic.label"
                        @click="seleccionarIcono(ic.key, cat.id)"
                      >
                        <SvgIcon :path="ic.path" :size="18" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <input :value="formCatEdit.nombre" @input="formCatEdit.nombre = ucfirst($event.target.value)" placeholder="Nombre" class="input-nombre" @keyup.enter="guardarEdicionCategoria(cat.id)" />
              <button @click="guardarEdicionCategoria(cat.id)" class="btn-icon btn-save" style="margin-left: 4px"><SvgIcon :path="mdiCheck" :size="15" /></button>
              <button @click="catEditando = null" class="btn-icon btn-del"><SvgIcon :path="mdiClose" :size="15" /></button>
            </div>

            <!-- Modo normal -->
            <template v-else>
              <span class="cat-icono">
                <SvgIcon v-if="cat.icono" :path="resolverIcono(cat.icono)" :size="20" />
                <SvgIcon v-else :path="mdiFormatListBulleted" :size="20" />
              </span>
              <span class="cat-nombre">{{ cat.nombre }}</span>
              <div class="cat-ord-btns">
                <button @click="moverCategoria(idx, -1)" class="btn-ord" :disabled="idx === 0" title="Subir">▲</button>
                <button @click="moverCategoria(idx, 1)" class="btn-ord" :disabled="idx === categorias.length - 1" title="Bajar">▼</button>
              </div>
              <button @click="iniciarEdicionCategoria(cat)" class="btn-icon btn-edit" title="Editar"><SvgIcon :path="mdiPencil" :size="15" /></button>
              <button @click="eliminarCategoria(cat.id)" class="btn-icon btn-del" title="Eliminar"><SvgIcon :path="mdiTrashCanOutline" :size="15" /></button>
            </template>
          </div>
        </div>

      </div>
    </div>

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
              <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
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
        <span class="count-badge">{{ productosFiltrados.length }}</span>
      </div>

      <!-- Pills de categoría -->
      <div v-if="categorias.length" class="cat-filter-row">
        <button
          :class="['filter-pill', { active: categoriaFiltro === null }]"
          @click="categoriaFiltro = null"
        >
          Todos <span class="pill-num">{{ productos.length }}</span>
        </button>
        <button
          v-for="cat in categorias"
          :key="cat.id"
          :class="['filter-pill', { active: categoriaFiltro === cat.id }]"
          @click="categoriaFiltro = cat.id"
        >
          <SvgIcon v-if="cat.icono" :path="resolverIcono(cat.icono)" :size="13" />
          {{ cat.nombre }}
          <span class="pill-num">{{ productos.filter(p => p.categoria_id === cat.id).length }}</span>
        </button>
      </div>

      <div class="card-body no-pad">
        <div v-if="loadingProductos && !productos.length" class="loading-inline"><div class="spinner"></div></div>
        <div v-else-if="!productosFiltrados.length" class="empty-state">
          <SvgIcon :path="mdiSilverwareForkKnife" :size="40" />
          <p>{{ categoriaFiltro ? 'Sin platillos en esta categoría.' : 'Sin platillos todavía.\nAgrega el primero arriba.' }}</p>
        </div>
        <div v-else class="prod-lista">
          <div v-for="prod in productosFiltrados" :key="prod.id" class="prod-item">
            <div class="prod-thumb" @click="abrirPreview(prod)" title="Ver foto">
              <img
                v-if="prod.foto_principal"
                :key="prod.foto_principal"
                :src="prod.foto_principal"
                :alt="prod.nombre"
                @error="($e) => $e.target.style.display='none'"
              />
              <span v-else class="thumb-empty"><SvgIcon :path="mdiCamera" :size="18" /></span>
              <div v-if="prod.foto_principal" class="thumb-overlay"><SvgIcon :path="mdiEye" :size="14" /></div>
            </div>
            <div class="prod-info">
              <strong class="prod-nombre">{{ prod.nombre }}</strong>
              <span class="prod-cat">{{ catMap[prod.categoria_id] || '—' }}</span>
              <span class="prod-precio">${{ Number(prod.precio).toFixed(2) }}</span>
            </div>
            <div class="prod-badges">
              <span v-if="prod.tiene_ar" class="badge badge-3d">3D ✓</span>
              <span v-else class="badge badge-no3d">Sin 3D</span>
              <label class="badge-disp" :title="prod.disponible ? 'Disponible — click para desactivar' : 'No disponible — click para activar'">
                <input type="checkbox" :checked="prod.disponible" @change="toggleDisponible(prod)" hidden />
                <span :class="['disp-pill', prod.disponible ? 'disp-on' : 'disp-off']">
                  {{ prod.disponible ? 'Activo' : 'Próximamente' }}
                </span>
              </label>
            </div>
            <div class="prod-actions">
              <!-- Desktop Actions -->
              <div class="desktop-actions">
                <button class="btn-icon btn-dots" @click="iniciarEdicion(prod)" title="Editar">
                  <SvgIcon :path="mdiPencil" :size="15" />
                </button>
                <label class="btn-icon btn-dots" title="Subir foto" style="cursor:pointer; margin: 0">
                  <SvgIcon :path="mdiImagePlus" :size="15" />
                  <input type="file" multiple accept="image/*" @change="subirFotos(prod.id, $event)" hidden />
                </label>
                <label v-if="!prod.tiene_ar" class="btn-icon btn-dots" title="Subir 3D" style="cursor:pointer; margin: 0">
                  <SvgIcon :path="mdiCubeOutline" :size="15" />
                  <input type="file" accept=".glb" @change="subirGlb(prod.id, $event)" hidden />
                </label>
                <button class="btn-icon btn-dots" style="color: #e74c3c" @click="eliminarProducto(prod.id)" title="Eliminar">
                  <SvgIcon :path="mdiTrashCanOutline" :size="15" />
                </button>
              </div>

              <!-- Mobile Actions Menu -->
              <div class="action-menu-wrap mobile-actions" @click.stop>
                <button
                  class="btn-icon btn-dots"
                  @click="menuAbierto = menuAbierto === prod.id ? null : prod.id"
                  title="Acciones"
                >
                  <SvgIcon :path="mdiDotsVertical" :size="18" />
                </button>
                <div v-if="menuAbierto === prod.id" class="action-dropdown">
                  <button class="action-item" @click="iniciarEdicion(prod); menuAbierto = null">
                    <SvgIcon :path="mdiPencil" :size="15" /> Editar
                  </button>
                  <label class="action-item">
                    <SvgIcon :path="mdiImagePlus" :size="15" /> Subir foto
                    <input type="file" multiple accept="image/*" @change="subirFotos(prod.id, $event); menuAbierto = null" hidden />
                  </label>
                  <label v-if="!prod.tiene_ar" class="action-item">
                    <SvgIcon :path="mdiCubeOutline" :size="15" /> Subir 3D
                    <input type="file" accept=".glb" @change="subirGlb(prod.id, $event); menuAbierto = null" hidden />
                  </label>
                  <button class="action-item action-item-del" @click="eliminarProducto(prod.id); menuAbierto = null">
                    <SvgIcon :path="mdiTrashCanOutline" :size="15" /> Eliminar
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ Modal de edición ═══ -->
    <Teleport to="body">
      <div v-if="prodEditando !== null" class="edit-modal-overlay" :style="{ '--accent': accent }" @click.self="cancelarEdicion">
        <div class="edit-modal">

          <!-- Header -->
          <div class="edit-modal-header">
            <div class="edit-modal-title-row">
              <div class="edit-modal-thumb">
                <img v-if="prodEditandoObj?.foto_principal" :src="prodEditandoObj.foto_principal" :alt="prodEditandoObj?.nombre" />
                <span v-else><SvgIcon :path="mdiCamera" :size="20" /></span>
              </div>
              <div class="edit-modal-title-text">
                <span class="edit-modal-label">Editando</span>
                <strong class="edit-modal-nombre">{{ prodEditandoObj?.nombre }}</strong>
              </div>
            </div>
            <button @click="cancelarEdicion" class="modal-close-btn" title="Cerrar"><SvgIcon :path="mdiClose" :size="18" /></button>
          </div>

          <!-- Body scrollable -->
          <div class="edit-modal-body">

            <!-- Sección: Datos básicos -->
            <div class="edit-section">
              <div class="edit-section-label">Información básica</div>
              <div class="edit-fields">
                <div class="field">
                  <label>Categoría</label>
                  <select v-model="formEdit.categoria_id">
                    <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
                  </select>
                </div>
                <div class="field">
                  <label>Nombre</label>
                  <input :value="formEdit.nombre" @input="formEdit.nombre = ucfirst($event.target.value)" placeholder="Nombre del platillo" />
                </div>
                <div class="field">
                  <label>Precio ($)</label>
                  <input v-model="formEdit.precio" type="number" min="0" step="0.01" placeholder="0.00" />
                </div>
                <div class="field field-full">
                  <label>Descripción</label>
                  <textarea :value="formEdit.descripcion" @input="formEdit.descripcion = ucfirst($event.target.value)" rows="2" placeholder="Descripción opcional..."></textarea>
                </div>
                <div class="field field-full">
                  <label>Stock</label>
                  <div class="edit-stock">
                    <template v-if="formEdit.stock === null">
                      <span class="stock-sin-ctrl">Sin control de stock</span>
                      <button type="button" class="btn-activar-stock" @click="formEdit.stock = 0">Activar</button>
                    </template>
                    <template v-else>
                      <button type="button" class="stock-btn" @click="formEdit.stock = Math.max(0, formEdit.stock - 1)">−</button>
                      <span class="stock-num">{{ formEdit.stock }}</span>
                      <button type="button" class="stock-btn" @click="formEdit.stock++">+</button>
                      <button type="button" class="btn-activar-stock" style="margin-left:8px" @click="formEdit.stock = null">Quitar control</button>
                    </template>
                  </div>
                </div>
              </div>
            </div>

            <!-- Sección: Personalización -->
            <div class="edit-section">
              <div class="edit-section-label pers-label-row">
                <span>Personalización por pasos</span>
                <label class="sw">
                  <input type="checkbox" v-model="formEdit.tiene_personalizacion" />
                  <span class="sw-track" :style="formEdit.tiene_personalizacion ? { background: accent } : {}"></span>
                </label>
              </div>

              <div v-if="formEdit.tiene_personalizacion" class="pers-section">

                <!-- Guía colapsable -->
                <div class="guia-wrap">
                  <button type="button" class="guia-toggle" @click="guiaAbierta = !guiaAbierta">
                    <span>{{ guiaAbierta ? '▲' : '▼' }}</span>
                    ¿Cómo funciona esto?
                  </button>
                  <div v-show="guiaAbierta" class="guia-body">
                    <div class="guia-item">
                      <span class="guia-icon"><SvgIcon :path="mdiRadioboxMarked" :size="18" /></span>
                      <div>
                        <strong>Única</strong> — El cliente elige <em>solo una</em> opción.<br>
                        <span class="guia-ej">Ej: Tamaño → Chico / Mediano / Grande</span>
                      </div>
                    </div>
                    <div class="guia-item">
                      <span class="guia-icon"><SvgIcon :path="mdiCheckboxMarked" :size="18" /></span>
                      <div>
                        <strong>Múltiple</strong> — El cliente puede elegir <em>varias</em> opciones hasta el máximo que configures.<br>
                        <span class="guia-ej">Ej: Ingredientes → Aguacate, Queso, Jalapeño...</span>
                      </div>
                    </div>
                    <div class="guia-item">
                      <span class="guia-icon"><SvgIcon :path="mdiPin" :size="18" /></span>
                      <div>
                        <strong>Requerido</strong> — El cliente no puede agregar al carrito sin elegir algo en este grupo.
                      </div>
                    </div>
                    <div class="guia-item">
                      <span class="guia-icon"><SvgIcon :path="mdiLink" :size="18" /></span>
                      <div>
                        <strong>Controla máx de</strong> — Un grupo Única puede definir cuántas opciones permite un grupo Múltiple.<br>
                        <span class="guia-ej">Ej: Tamaño Chico → máx 2 ingredientes / Grande → máx 5.</span><br>
                        <span class="guia-ej">Actívalo en el grupo Única, luego pon el número en cada opción bajo "Máx".</span>
                      </div>
                    </div>
                    <div class="guia-item">
                      <span class="guia-icon"><SvgIcon :path="mdiMessageText" :size="18" /></span>
                      <div>
                        <strong>Aviso sugerido</strong> — Texto que aparece después de agregar al carrito. Ideal para sugerir bebidas o complementos.<br>
                        <span class="guia-ej">Ej: "¿Quieres agregar una bebida?" → apunta a la categoría Bebidas.</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Aviso complemento -->
                <div class="field">
                  <label>Aviso sugerido <span class="label-opt">(opcional)</span></label>
                  <input v-model="formEdit.aviso_complemento" placeholder="Ej: ¿Quieres agregar una bebida?" />
                </div>
                <div class="field">
                  <label>Categoría a la que lleva el aviso</label>
                  <select v-model="formEdit.aviso_categoria_id">
                    <option value="">Sin categoría</option>
                    <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
                  </select>
                </div>

                <!-- Grupos -->
                <div v-if="gruposCargando" class="loading-inline"><div class="spinner"></div></div>
                <div v-else class="grupos-editor">
                  <div class="grupos-label">Grupos de opciones</div>

                  <div v-for="(grupo, gi) in formEdit.grupos" :key="grupo._key" class="grupo-card">
                    <div class="grupo-head">
                      <input
                        :value="grupo.nombre"
                        @input="grupo.nombre = ucfirst($event.target.value)"
                        placeholder="Nombre del grupo (ej: Tamaño)"
                        class="grupo-nombre-input"
                      />
                      <div class="grupo-tipo-btns" title="Única: elige solo una opción (ej: Tamaño). Múltiple: puede elegir varias (ej: Ingredientes).">
                        <button
                          type="button"
                          :class="['tipo-btn', { active: grupo.tipo === 'radio' }]"
                          @click="grupo.tipo = 'radio'"
                        >
                          <SvgIcon :path="mdiRadioboxMarked" :size="14" /> Única
                        </button>
                        <button
                          type="button"
                          :class="['tipo-btn', { active: grupo.tipo === 'checkbox' }]"
                          @click="grupo.tipo = 'checkbox'"
                        >
                          <SvgIcon :path="mdiCheckboxMarked" :size="14" /> Múltiple
                        </button>
                      </div>
                      <button type="button" @click="eliminarGrupo(gi)" class="btn-del-grupo" title="Eliminar grupo"><SvgIcon :path="mdiClose" :size="14" /></button>
                    </div>

                    <div class="grupo-config">
                      <div class="config-check" title="Si está activo, el cliente no puede continuar sin elegir algo aquí">
                        <span class="config-check-label">Requerido</span>
                        <label class="sw">
                          <input type="checkbox" v-model="grupo.obligatorio" />
                          <span class="sw-track" :style="grupo.obligatorio ? { background: accent } : {}"></span>
                        </label>
                      </div>
                      <label v-if="grupo.tipo === 'checkbox'" class="config-max" title="Cuántas opciones puede seleccionar el cliente como máximo">
                        Máx opciones:
                        <input type="number" v-model.number="grupo.max_selecciones" min="1" max="20" class="input-max" />
                      </label>
                      <label
                        v-if="grupo.tipo === 'radio' && formEdit.grupos.some((g, i) => i !== gi && g.tipo === 'checkbox')"
                        class="config-din"
                        title="Este grupo Única controlará cuántas opciones permite el grupo seleccionado. Configura el número en cada opción abajo (Máx)."
                      >
                        Controla máx de:
                        <select v-model="grupo.max_dinamico_grupo_index" class="input-din">
                          <option :value="null">Ninguno</option>
                          <template v-for="(g, i) in formEdit.grupos" :key="g._key">
                            <option v-if="i !== gi && g.tipo === 'checkbox'" :value="i">
                              {{ g.nombre || `Grupo ${i + 1}` }}
                            </option>
                          </template>
                        </select>
                      </label>
                    </div>

                    <div class="opciones-edit">
                      <div v-if="grupo.opciones.length" class="opciones-header">
                        <span class="col-nombre">Opción</span>
                        <span class="col-precio">Precio</span>
                        <span v-if="grupo.tipo === 'radio' && grupo.max_dinamico_grupo_index !== null" class="col-max">Items</span>
                      </div>
                      <div v-for="(op, oi) in grupo.opciones" :key="op._key" class="opcion-edit-row">
                        <input
                          :value="op.nombre"
                          @input="op.nombre = ucfirst($event.target.value)"
                          placeholder="Opción"
                          class="op-nombre"
                        />
                        <span class="op-extra-wrap" title="Precio extra que se suma al platillo si eligen esta opción">
                          +$<input type="number" v-model.number="op.precio_extra" min="0" step="0.5" class="op-extra" />
                        </span>
                        <span
                          v-if="grupo.tipo === 'radio' && grupo.max_dinamico_grupo_index !== null"
                          class="op-override-wrap"
                          title="Cuántas opciones puede elegir el cliente en el grupo Múltiple si selecciona esta opción"
                        >
                          Máx:<input type="number" v-model.number="op.max_override" min="0" class="op-override" placeholder="—" />
                        </span>
                        <button type="button" @click="eliminarOpcion(gi, oi)" class="btn-del-op" title="Eliminar opción"><SvgIcon :path="mdiClose" :size="13" /></button>
                      </div>
                      <button type="button" @click="agregarOpcion(gi)" class="btn-add-op">+ agregar opción</button>
                    </div>
                  </div>

                  <button type="button" @click="agregarGrupo" class="btn-add-grupo">+ Agregar grupo</button>
                </div>
              </div>
            </div>

          </div><!-- /.edit-modal-body -->

          <!-- Footer sticky -->
          <div class="edit-modal-footer">
            <button @click="cancelarEdicion" class="btn-secondary">Cancelar</button>
            <button @click="guardarEdicionProducto" class="btn-primary" :disabled="guardando">
              <SvgIcon v-if="!guardando" :path="mdiCheck" :size="16" />
              {{ guardando ? 'Guardando...' : 'Guardar cambios' }}
            </button>
          </div>

        </div>
      </div>
    </Teleport>

    <!-- Modal preview de foto -->
    <div v-if="preview" class="preview-overlay" @click="preview = null">
      <div class="preview-box" @click.stop>
        <button class="preview-close" @click="preview = null"><SvgIcon :path="mdiClose" :size="16" /></button>
        <img :src="preview.url" :alt="preview.nombre" class="preview-img" />
        <p class="preview-nombre">{{ preview.nombre }}</p>
      </div>
    </div>

    <UploadToast :model-value="uploadToast" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import {
  mdiSilverwareForkKnife, mdiCamera, mdiEye, mdiPencil, mdiImagePlus,
  mdiCubeOutline, mdiTrashCanOutline, mdiClose, mdiCheck,
  mdiRadioboxMarked, mdiCheckboxMarked, mdiPin, mdiLink, mdiMessageText,
  mdiDotsVertical, mdiFormatListBulleted
} from '@mdi/js'
import { useApi } from '../../../composables/useApi.js'
import { useUpload } from '../../../composables/useUpload.js'
import { ucfirst } from '../../../utils/ucfirst.js'
import { ICONO_GRUPOS, resolverIcono } from '../../../utils/iconosCategorias.js'
import SvgIcon from '../../SvgIcon.vue'
import UploadToast from '../UploadToast.vue'

const props = defineProps({
  restauranteId: { type: Number, required: true },
  categorias:    { type: Array, default: () => [] },
  accent:        { type: String, default: '#FF6B35' },
})

const emit = defineEmits(['notif', 'categorias-changed'])

const { get, post, put, del } = useApi()
const { uploadToast, xhrUpload } = useUpload()

const productos        = ref([])
const loadingProductos = ref(false)
const guardando        = ref(false)
const formAbierto      = ref(false)
const prodEditando     = ref(null)
const formEdit         = ref({})
const preview          = ref(null)
const gruposCargando   = ref(false)
const categoriaFiltro  = ref(null)
const guiaAbierta      = ref(false)
const menuAbierto      = ref(null)

const catAbierto       = ref(false)
const catEditando      = ref(null)
const formCatEdit      = ref({})
const pickerAbierto    = ref(null)
const formCat          = ref({ nombre: '', icono: '' })
const guardandoCat     = ref(false)

const formProd = ref({ categoria_id: '', nombre: '', precio: '', descripcion: '' })

const togglePicker = (id) => { pickerAbierto.value = pickerAbierto.value === id ? null : id }
const seleccionarIcono = (key, target) => {
  if (target === 'nuevo') formCat.value.icono = key
  else formCatEdit.value.icono = key
  pickerAbierto.value = null
}
const cerrarPickerGlobal = (e) => {
  if (!e.target.closest('.icono-wrap')) pickerAbierto.value = null
}
const iniciarEdicionCategoria = (cat) => {
  catEditando.value = cat.id
  formCatEdit.value = { nombre: cat.nombre, icono: cat.icono || '' }
}

async function crearCategoria() {
  if (!formCat.value.nombre.trim()) { emit('notif', { texto: 'Escribe un nombre', tipo: 'error' }); return }
  guardandoCat.value = true
  try {
    await post('categorias', { restaurante_id: props.restauranteId, nombre: formCat.value.nombre.trim(), icono: formCat.value.icono || null })
    formCat.value = { nombre: '', icono: '' }
    emit('categorias-changed')
    emit('notif', { texto: 'Categoría creada', tipo: 'ok' })
  } catch (err) { emit('notif', { texto: err.message, tipo: 'error' }) }
  finally { guardandoCat.value = false }
}

async function guardarEdicionCategoria(id) {
  if (!formCatEdit.value.nombre?.trim()) { emit('notif', { texto: 'El nombre es requerido', tipo: 'error' }); return }
  try {
    await put('categorias', { nombre: formCatEdit.value.nombre.trim(), icono: formCatEdit.value.icono || null }, { id })
    catEditando.value = null
    emit('categorias-changed')
    emit('notif', { texto: 'Categoría actualizada', tipo: 'ok' })
  } catch (err) { emit('notif', { texto: err.message, tipo: 'error' }) }
}

async function eliminarCategoria(id) {
  if (!confirm('¿Eliminar esta categoría y todos sus platillos?')) return
  try {
    await del('categorias', { id })
    emit('categorias-changed')
    emit('notif', { texto: 'Categoría eliminada', tipo: 'ok' })
  } catch (err) { emit('notif', { texto: err.message, tipo: 'error' }) }
}

async function moverCategoria(idx, dir) {
  const arr = [...props.categorias]
  const newIdx = idx + dir
  if (newIdx < 0 || newIdx >= arr.length) return
  ;[arr[idx], arr[newIdx]] = [arr[newIdx], arr[idx]]
  arr.forEach((c, i) => { c.orden = i })
  try {
    await Promise.all([
      put('categorias', { orden: arr[idx].orden }, { id: arr[idx].id }),
      put('categorias', { orden: arr[newIdx].orden }, { id: arr[newIdx].id }),
    ])
    emit('categorias-changed')
  } catch (err) {
    emit('notif', { texto: err.message, tipo: 'error' })
    emit('categorias-changed')
  }
}

let _uid = 0
const newKey = () => ++_uid

const catMap = computed(() => {
  const m = {}
  props.categorias.forEach(c => { m[c.id] = c.nombre })
  return m
})

const productosOrdenados = computed(() =>
  [...productos.value].sort((a, b) => {
    const ca = catMap.value[a.categoria_id] || ''
    const cb = catMap.value[b.categoria_id] || ''
    return ca.localeCompare(cb) || a.nombre.localeCompare(b.nombre)
  })
)

const productosFiltrados = computed(() =>
  categoriaFiltro.value === null
    ? productosOrdenados.value
    : productosOrdenados.value.filter(p => p.categoria_id === categoriaFiltro.value)
)

const prodEditandoObj = computed(() =>
  productos.value.find(p => p.id === prodEditando.value) || null
)

async function loadProductos() {
  loadingProductos.value = true
  try {
    const res = await get('productos', { restaurante_id: props.restauranteId })
    productos.value = res.productos || []
  } finally {
    loadingProductos.value = false
  }
}

async function crearProducto() {
  const f = formProd.value
  if (!f.categoria_id || !f.nombre.trim()) {
    emit('notif', { texto: 'Categoría y nombre son requeridos', tipo: 'error' })
    return
  }
  if (f.precio === '' || isNaN(parseFloat(f.precio)) || parseFloat(f.precio) < 0) {
    emit('notif', { texto: 'El precio no puede ser negativo', tipo: 'error' })
    return
  }
  guardando.value = true
  try {
    await post('productos', { categoria_id: f.categoria_id, nombre: f.nombre.trim(), precio: parseFloat(f.precio), descripcion: f.descripcion.trim() })
    formProd.value = { categoria_id: '', nombre: '', precio: '', descripcion: '' }
    await loadProductos()
    emit('notif', { texto: 'Platillo agregado', tipo: 'ok' })
  } catch (err) { emit('notif', { texto: err.message, tipo: 'error' }) }
  finally { guardando.value = false }
}

async function eliminarProducto(id) {
  if (!confirm('¿Eliminar este platillo del menú?')) return
  try {
    await del('productos', { id })
    await loadProductos()
    emit('notif', { texto: 'Platillo eliminado', tipo: 'ok' })
  } catch (err) { emit('notif', { texto: err.message, tipo: 'error' }) }
}

const iniciarEdicion = async (prod) => {
  prodEditando.value = prod.id
  guiaAbierta.value = false
  formEdit.value = {
    categoria_id:          prod.categoria_id,
    nombre:                prod.nombre,
    precio:                prod.precio,
    descripcion:           prod.descripcion || '',
    stock:                 prod.stock ?? null,
    tiene_personalizacion: !!prod.tiene_personalizacion,
    aviso_complemento:     prod.aviso_complemento || '',
    aviso_categoria_id:    prod.aviso_categoria_id || '',
    grupos:                [],
  }
  if (prod.tiene_personalizacion) {
    await loadGrupos(prod.id)
  }
}

const loadGrupos = async (productoId) => {
  gruposCargando.value = true
  try {
    const res = await get('producto-grupos', { producto_id: productoId })
    const gruposApi = res.grupos || []
    const idToIndex = {}
    gruposApi.forEach((g, i) => { idToIndex[g.id] = i })

    formEdit.value.grupos = gruposApi.map(g => ({
      _key:                     newKey(),
      nombre:                   g.nombre,
      tipo:                     g.tipo,
      obligatorio:              g.obligatorio,
      max_selecciones:          g.max_selecciones,
      max_dinamico_grupo_index: g.max_dinamico_grupo_id != null
        ? (idToIndex[g.max_dinamico_grupo_id] ?? null)
        : null,
      opciones: g.opciones.map(o => ({
        _key:         newKey(),
        nombre:       o.nombre,
        precio_extra: o.precio_extra,
        max_override: o.max_override,
      })),
    }))
  } finally {
    gruposCargando.value = false
  }
}

const agregarGrupo = () => {
  formEdit.value.grupos.push({
    _key: newKey(), nombre: '', tipo: 'radio',
    obligatorio: true, max_selecciones: 1,
    max_dinamico_grupo_index: null, opciones: [],
  })
}

const eliminarGrupo = (idx) => {
  formEdit.value.grupos.splice(idx, 1)
  formEdit.value.grupos.forEach(g => {
    if (g.max_dinamico_grupo_index === idx) g.max_dinamico_grupo_index = null
    else if (g.max_dinamico_grupo_index !== null && g.max_dinamico_grupo_index > idx)
      g.max_dinamico_grupo_index--
  })
}

const agregarOpcion = (gi) => {
  formEdit.value.grupos[gi].opciones.push({ _key: newKey(), nombre: '', precio_extra: 0, max_override: null })
}

const eliminarOpcion = (gi, oi) => {
  formEdit.value.grupos[gi].opciones.splice(oi, 1)
}

const cancelarEdicion = () => {
  prodEditando.value = null
  formEdit.value = {}
  guiaAbierta.value = false
}

const toggleDisponible = async (prod) => {
  const nuevoValor = !prod.disponible
  try {
    await put('productos', { disponible: nuevoValor ? 1 : 0 }, { id: prod.id })
    prod.disponible = nuevoValor
    emit('notif', { texto: nuevoValor ? 'Platillo activado' : 'Platillo desactivado', tipo: 'ok' })
  } catch (err) { emit('notif', { texto: err.message, tipo: 'error' }) }
}

const guardarEdicionProducto = async () => {
  const id = prodEditando.value
  const f  = formEdit.value

  // ── Validación campos básicos ──
  if (!f.nombre?.trim()) {
    emit('notif', { texto: 'El nombre es requerido', tipo: 'error' })
    return
  }
  if (f.precio === '' || isNaN(parseFloat(f.precio)) || parseFloat(f.precio) < 0) {
    emit('notif', { texto: 'El precio no puede ser negativo', tipo: 'error' })
    return
  }

  // ── Validación de grupos/opciones ──
  if (f.tiene_personalizacion) {
    for (let gi = 0; gi < f.grupos.length; gi++) {
      const g = f.grupos[gi]
      if (!g.nombre?.trim()) {
        emit('notif', { texto: `El grupo ${gi + 1} no tiene nombre`, tipo: 'error' })
        return
      }
      if (g.tipo === 'checkbox' && (parseInt(g.max_selecciones) < 1 || isNaN(parseInt(g.max_selecciones)))) {
        emit('notif', { texto: `"${g.nombre}": máx opciones debe ser al menos 1`, tipo: 'error' })
        return
      }
      if (g.opciones.length === 0) {
        emit('notif', { texto: `El grupo "${g.nombre}" debe tener al menos una opción`, tipo: 'error' })
        return
      }
      for (let oi = 0; oi < g.opciones.length; oi++) {
        const o = g.opciones[oi]
        if (!o.nombre?.trim()) {
          emit('notif', { texto: `Opción ${oi + 1} del grupo "${g.nombre}" no tiene nombre`, tipo: 'error' })
          return
        }
        if (parseFloat(o.precio_extra) < 0) {
          emit('notif', { texto: `"${o.nombre}": el precio extra no puede ser negativo`, tipo: 'error' })
          return
        }
      }
    }
  }

  guardando.value = true

  // ── 1. Guardar campos básicos ──
  try {
    const payload = {
      categoria_id: f.categoria_id,
      nombre:       f.nombre.trim(),
      precio:       parseFloat(f.precio),
      descripcion:  f.descripcion.trim(),
    }
    payload.stock = f.stock  // null = quitar control; número = stock con control
    await put('productos', payload, { id })
  } catch (err) {
    emit('notif', { texto: 'Error al guardar el platillo: ' + err.message, tipo: 'error' })
    guardando.value = false
    return
  }

  // ── 2. Guardar personalización ──
  try {
    await post('producto-grupos', {
      producto_id:           id,
      tiene_personalizacion: f.tiene_personalizacion ? 1 : 0,
      aviso_complemento:     f.aviso_complemento?.trim() || null,
      aviso_categoria_id:    f.aviso_categoria_id || null,
      grupos: f.tiene_personalizacion
        ? f.grupos.map((g, gi) => ({
            nombre:                   g.nombre.trim(),
            tipo:                     g.tipo,
            obligatorio:              g.obligatorio ? 1 : 0,
            min_selecciones:          0,
            max_selecciones:          parseInt(g.max_selecciones) || 1,
            max_dinamico_grupo_index: g.max_dinamico_grupo_index,
            orden:                    gi,
            opciones: g.opciones.map((o, oi) => ({
              nombre:       o.nombre.trim(),
              precio_extra: parseFloat(o.precio_extra) || 0,
              max_override: o.max_override !== null && o.max_override !== ''
                ? parseInt(o.max_override) : null,
              orden: oi,
            })),
          }))
        : [],
    })
  } catch (err) {
    await loadProductos()
    emit('notif', { texto: 'Datos básicos guardados. Error al guardar personalización: ' + err.message, tipo: 'error' })
    guardando.value = false
    return
  }

  prodEditando.value = null
  guiaAbierta.value = false
  await loadProductos()
  emit('notif', { texto: 'Platillo actualizado', tipo: 'ok' })
  guardando.value = false
}

async function subirFotos(prodId, event) {
  const files = event.target.files
  if (!files.length) return
  const fd = new FormData()
  fd.append('producto_id', prodId)
  for (let i = 0; i < files.length; i++) fd.append('fotos[]', files[i])
  try {
    await xhrUpload(`${import.meta.env.BASE_URL}api/?route=upload-fotos`, fd, 'Subiendo foto…')
    await loadProductos()
    emit('notif', { texto: 'Foto subida correctamente', tipo: 'ok' })
  } catch (err) {
    emit('notif', { texto: err.message, tipo: 'error' })
  } finally {
    event.target.value = ''
  }
}

async function subirGlb(prodId, event) {
  const file = event.target.files[0]
  if (!file) return
  const fd = new FormData()
  fd.append('producto_id', prodId)
  fd.append('modelo', file)
  try {
    await xhrUpload(`${import.meta.env.BASE_URL}api/?route=upload-glb`, fd, 'Subiendo modelo 3D…')
    await loadProductos()
    emit('notif', { texto: 'Modelo 3D subido. ¡Ya disponible en el menú!', tipo: 'ok' })
  } catch (err) {
    emit('notif', { texto: err.message, tipo: 'error' })
  } finally {
    event.target.value = ''
  }
}

const abrirPreview = (prod) => {
  if (!prod.foto_principal) return
  preview.value = { url: prod.foto_principal, nombre: prod.nombre }
}

const cerrarMenu = () => { menuAbierto.value = null }

let _platillosVisibilityFn = null

onMounted(() => {
  loadProductos()
  document.addEventListener('click', cerrarMenu)
  document.addEventListener('click', cerrarPickerGlobal)
  _platillosVisibilityFn = () => { if (!document.hidden) loadProductos() }
  document.addEventListener('visibilitychange', _platillosVisibilityFn)
})
onUnmounted(() => {
  document.removeEventListener('click', cerrarMenu)
  document.removeEventListener('click', cerrarPickerGlobal)
  if (_platillosVisibilityFn) document.removeEventListener('visibilitychange', _platillosVisibilityFn)
})
</script>

<style scoped>
/* ─── Categorías ─── */
.cat-lista { display: flex; flex-direction: column; }
.cat-item {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 16px; border-bottom: 1px solid #f5f5f5;
}
.cat-item:last-child { border-bottom: none; }
.cat-icono  { width: 28px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--accent, #FF6B35); }
.cat-nombre { flex: 1; font-weight: 600; font-size: 0.9rem; color: #333; }
.cat-ord-btns { display: flex; flex-direction: column; gap: 2px; flex-shrink: 0; }
.btn-ord { width: 22px; height: 18px; border: 1px solid #e0e0e0; border-radius: 4px; background: #fafafa; font-size: 0.6rem; cursor: pointer; line-height: 1; padding: 0; }
.btn-ord:hover:not(:disabled) { background: #eee; }
.btn-ord:disabled { opacity: 0.25; cursor: default; }

/* Edición inline */
.cat-edit-form { display: flex; align-items: center; gap: 8px; width: 100%; }
.input-nombre { flex: 1; padding: 7px 10px; border: 1.5px solid #e0e0e0; border-radius: 7px; font-size: 0.9rem; outline: none; }
.input-nombre:focus { border-color: var(--accent); }

/* ─── Icon picker ─── */
.icono-wrap { position: relative; display: inline-block; }
.icono-btn {
  display: flex; align-items: center; gap: 6px;
  padding: 9px 12px; border: 1.5px solid #e0e0e0; border-radius: 8px;
  background: #fafafa; cursor: pointer; transition: border-color 0.2s, background 0.2s; white-space: nowrap;
  color: var(--accent, #FF6B35);
}
.icono-btn:hover { border-color: var(--accent); background: #fff; }
.icono-btn-sm { padding: 6px 10px; }
.picker-caret  { font-size: 0.65rem; color: #bbb; }

.icono-picker {
  position: absolute; top: calc(100% + 6px); left: 0; z-index: 300;
  background: #fff; border: 1px solid #e8e8e8; border-radius: 14px;
  box-shadow: 0 10px 36px rgba(0,0,0,0.16); padding: 14px;
  width: 300px; max-height: 360px; overflow-y: auto; scrollbar-width: thin;
}
.icono-picker-right { left: 0; right: auto; }
.icono-grupo { margin-bottom: 12px; }
.icono-grupo:last-child { margin-bottom: 0; }
.icono-grupo-titulo { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.7px; color: #bbb; margin-bottom: 6px; padding-left: 2px; }
.icono-grid { display: grid; grid-template-columns: repeat(8, 1fr); gap: 2px; }
.icono-opt {
  width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
  border: none; background: transparent; cursor: pointer;
  border-radius: 6px; transition: background 0.12s, transform 0.12s; padding: 0;
  color: #555;
}
.icono-opt:hover { background: #f0f0f0; transform: scale(1.2); color: var(--accent, #FF6B35); }
.icono-opt.selected { background: #fff3e0; outline: 2px solid var(--accent); color: var(--accent, #FF6B35); }

/* ─── Lista de productos ─── */
.prod-lista { display: flex; flex-direction: column; }

.prod-item {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 16px; border-bottom: 1px solid #f5f5f5; transition: background 0.15s;
}
.prod-item:last-child { border-bottom: none; }
.prod-item:hover { background: #fafafa; }

/* Miniatura */
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

/* Info */
.prod-info { flex: 1; display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.prod-nombre { font-size: 0.92rem; font-weight: 700; color: #1a1a1a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.prod-cat    { font-size: 0.75rem; color: #aaa; }
.prod-precio { font-size: 0.88rem; font-weight: 700; color: var(--accent); }

/* Badges */
.prod-badges { flex-shrink: 0; display: flex; flex-direction: column; align-items: flex-end; gap: 5px; }
.badge        { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; }
.badge-3d     { background: #e8f5e9; color: #2e7d32; }
.badge-no3d   { background: #f5f5f5; color: #bbb; }
.badge-disp { cursor: pointer; }
.disp-pill {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 4px 10px; border-radius: 20px;
  font-size: 0.72rem; font-weight: 700;
  border: 1.5px solid currentColor;
  transition: opacity 0.15s, transform 0.1s;
  user-select: none;
}
.badge-disp:hover .disp-pill { opacity: 0.78; transform: scale(0.97); }
.disp-pill::after { content: '⇅'; font-size: 0.65rem; opacity: 0.6; }
.disp-on  { background: #e8f5e9; color: #2e7d32; }
.disp-off { background: #fff8e1; color: #e65100; }

.prod-actions { display: flex; gap: 5px; flex-shrink: 0; }

/* ─── Pills de categoría ─── */
.cat-filter-row {
  display: flex; gap: 6px; padding: 10px 16px 0;
  overflow-x: auto; scrollbar-width: none; -ms-overflow-style: none;
}
.cat-filter-row::-webkit-scrollbar { display: none; }

.filter-pill {
  flex-shrink: 0; display: flex; align-items: center; gap: 5px;
  padding: 5px 12px; border-radius: 20px; border: 1.5px solid #e0e0e0;
  background: #fafafa; color: #666; font-size: 0.8rem; font-weight: 600;
  cursor: pointer; transition: all 0.15s; white-space: nowrap;
}
.filter-pill:hover { border-color: var(--accent); color: var(--accent); background: #fff; }
.filter-pill.active { background: var(--accent); border-color: var(--accent); color: #fff; }
.filter-pill.active .pill-num { background: rgba(255,255,255,0.25); color: #fff; }

.pill-num {
  display: inline-flex; align-items: center; justify-content: center;
  background: #e8e8e8; color: #888; border-radius: 10px;
  font-size: 0.7rem; font-weight: 700; min-width: 18px; height: 18px; padding: 0 5px;
}

/* ─── Modal de edición ─── */
.edit-modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.5);
  display: flex; align-items: center; justify-content: center;
  z-index: 1000; padding: 16px;
}

.edit-modal {
  background: #fff; border-radius: 18px;
  width: 100%; max-width: 560px; max-height: 90vh;
  display: flex; flex-direction: column; overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,0.25);
}

.edit-modal-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 20px; border-bottom: 1px solid #f0f0f0; flex-shrink: 0;
}
.edit-modal-title-row { display: flex; align-items: center; gap: 12px; }
.edit-modal-thumb {
  width: 46px; height: 46px; border-radius: 10px; background: #f0f0f0;
  overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
}
.edit-modal-thumb img { width: 100%; height: 100%; object-fit: cover; }
.edit-modal-label { font-size: 0.72rem; color: #aaa; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; display: block; }
.edit-modal-nombre { font-size: 0.97rem; font-weight: 700; color: #1a1a1a; }

.modal-close-btn {
  width: 34px; height: 34px; border-radius: 50%; border: none;
  background: #f0f0f0; color: #666; font-size: 0.9rem;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; transition: background 0.15s;
}
.modal-close-btn:hover { background: #e0e0e0; }

.edit-modal-body {
  flex: 1; overflow-y: auto; padding: 0 20px 8px;
  scrollbar-width: thin; scrollbar-color: #e0e0e0 transparent;
}

.edit-modal-footer {
  display: flex; justify-content: flex-end; gap: 10px;
  padding: 14px 20px; border-top: 1px solid #f0f0f0; flex-shrink: 0;
}

/* ─── Secciones del modal ─── */
.edit-section {
  border-bottom: 1px solid #f5f5f5;
  padding: 18px 0;
}
.edit-section:last-child { border-bottom: none; }

.edit-section-label {
  font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.06em; color: #bbb; margin-bottom: 12px;
}
.pers-label-row {
  display: flex; align-items: center; justify-content: space-between;
}

.edit-fields { display: flex; flex-direction: column; gap: 10px; }

.edit-fields select, .edit-fields input, .edit-fields textarea {
  width: 100%; padding: 9px 12px; border: 1.5px solid #e0e0e0; border-radius: 8px;
  font-size: 0.88rem; outline: none; font-family: inherit; background: #fff;
  box-sizing: border-box;
}
.edit-fields select:focus, .edit-fields input:focus, .edit-fields textarea:focus { border-color: var(--accent); }
.edit-fields textarea { resize: vertical; min-height: 56px; }

.field-full { width: 100%; }

/* Stock */
.edit-stock { display: flex; align-items: center; gap: 8px; }
.stock-sin-ctrl { font-size: 0.8rem; color: #bbb; }
.btn-activar-stock { font-size: 0.82rem; font-weight: 600; padding: 6px 12px; border: none; border-radius: 7px; background: #e3f2fd; color: #1565c0; cursor: pointer; flex-shrink: 0; }
.btn-activar-stock:hover { background: #bbdefb; }
.stock-btn { width: 28px; height: 28px; border-radius: 50%; border: 1.5px solid #e0e0e0; background: #fff; font-size: 1rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.stock-btn:hover { background: #f0f0f0; }
.stock-num { font-size: 0.95rem; font-weight: 800; min-width: 28px; text-align: center; color: #1a1a1a; }


/* ─── Sección personalización ─── */
.pers-section { display: flex; flex-direction: column; gap: 10px; padding-top: 4px; }

.label-opt { font-size: 0.72rem; color: #bbb; font-weight: 400; }

/* Guía colapsable */
.guia-wrap { border: 1.5px solid #e8e8e8; border-radius: 10px; overflow: hidden; }

.guia-toggle {
  width: 100%; display: flex; align-items: center; gap: 8px;
  padding: 10px 14px; background: #f8f8f8; border: none; cursor: pointer;
  font-size: 0.83rem; font-weight: 700; color: #555; text-align: left;
  transition: background 0.15s;
}
.guia-toggle:hover { background: #f0f0f0; }
.guia-toggle span { font-size: 0.65rem; color: #aaa; }

.guia-body {
  padding: 12px 14px; display: flex; flex-direction: column; gap: 10px;
  background: #fff; border-top: 1px solid #f0f0f0;
}
.guia-item { display: flex; gap: 10px; align-items: flex-start; font-size: 0.82rem; color: #555; line-height: 1.5; }
.guia-icon { display: inline-flex; flex-shrink: 0; margin-top: 1px; color: #888; }
.guia-ej { font-size: 0.77rem; color: #aaa; font-style: normal; }

/* Grupos */
.grupos-editor { display: flex; flex-direction: column; gap: 8px; }
.grupos-label  { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #bbb; }

.grupo-card {
  border: 1.5px solid #e8e8e8; border-radius: 10px;
  padding: 10px 12px; display: flex; flex-direction: column; gap: 8px; background: #fafafa;
}
.grupo-head { display: flex; align-items: center; gap: 6px; }
.grupo-nombre-input {
  flex: 1; min-width: 0; padding: 6px 9px; border: 1.5px solid #e0e0e0; border-radius: 6px;
  font-size: 0.85rem; font-family: inherit; outline: none; background: #fff;
}
.grupo-nombre-input:focus { border-color: var(--accent); }

.grupo-tipo-btns { display: flex; border: 1.5px solid #e0e0e0; border-radius: 6px; overflow: hidden; flex-shrink: 0; }
.tipo-btn {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 5px 10px; border: none; background: #fff; color: #888;
  font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: background 0.15s, color 0.15s;
}
.tipo-btn + .tipo-btn { border-left: 1.5px solid #e0e0e0; }
.tipo-btn.active { background: var(--accent); color: #fff; }
.btn-del-grupo {
  flex-shrink: 0; width: 26px; height: 26px; border: none; border-radius: 50%;
  background: #fdecea; color: #c62828; font-size: 0.8rem; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
}
.btn-del-grupo:hover { background: #ffcdd2; }

.grupo-config { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; }
.config-check { display: flex; align-items: center; gap: 6px; font-size: 0.82rem; color: #555; }
.config-check-label { font-size: 0.82rem; color: #555; }
.config-max, .config-din { display: flex; align-items: center; gap: 5px; font-size: 0.82rem; color: #555; }
.input-max { width: 52px; padding: 4px 6px; border: 1.5px solid #e0e0e0; border-radius: 5px; font-size: 0.82rem; text-align: center; outline: none; }
.input-max:focus { border-color: var(--accent); }
.input-din { padding: 4px 6px; border: 1.5px solid #e0e0e0; border-radius: 5px; font-size: 0.82rem; outline: none; background: #fff; }
.input-din:focus { border-color: var(--accent); }

/* Opciones del grupo */
.opciones-edit { display: flex; flex-direction: column; gap: 5px; padding-top: 4px; border-top: 1px solid #efefef; }
.opciones-header {
  display: flex; align-items: center; gap: 5px;
  padding: 0 2px; margin-bottom: -2px;
}
.col-nombre  { flex: 1; min-width: 0; font-size: 0.72rem; color: #bbb; }
.col-precio  { width: 62px; font-size: 0.72rem; color: #bbb; text-align: center; flex-shrink: 0; }
.col-max     { width: 62px; font-size: 0.72rem; color: #bbb; text-align: center; flex-shrink: 0; margin-right: 27px; }
.opcion-edit-row { display: flex; align-items: center; gap: 5px; }
.op-nombre {
  flex: 1; min-width: 0; padding: 5px 7px; border: 1px solid #e0e0e0; border-radius: 5px;
  font-size: 0.82rem; font-family: inherit; outline: none; background: #fff;
}
.op-nombre:focus { border-color: var(--accent); }
.op-extra-wrap, .op-override-wrap {
  display: flex; align-items: center; gap: 2px; font-size: 0.75rem; color: #aaa; flex-shrink: 0;
}
.op-extra { width: 46px; padding: 4px 4px; border: 1px solid #e0e0e0; border-radius: 5px; font-size: 0.8rem; text-align: right; outline: none; }
.op-extra:focus { border-color: var(--accent); }
.op-override { width: 36px; padding: 4px 4px; border: 1px solid #e0e0e0; border-radius: 5px; font-size: 0.8rem; text-align: center; outline: none; }
.op-override:focus { border-color: var(--accent); }
.btn-del-op {
  flex-shrink: 0; width: 22px; height: 22px; border: none; border-radius: 50%;
  background: #fdecea; color: #c62828; font-size: 0.72rem; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
}
.btn-del-op:hover { background: #ffcdd2; }
.btn-add-op {
  align-self: flex-start; padding: 4px 10px; border: 1.5px dashed #ccc; border-radius: 6px;
  background: transparent; color: #888; font-size: 0.8rem; cursor: pointer; margin-top: 2px;
}
.btn-add-op:hover { border-color: var(--accent); color: var(--accent); }
.btn-add-grupo {
  align-self: flex-start; padding: 7px 14px; border: 1.5px dashed #ccc; border-radius: 8px;
  background: transparent; color: #888; font-size: 0.85rem; font-weight: 600; cursor: pointer;
}
.btn-add-grupo:hover { border-color: var(--accent); color: var(--accent); }

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
.preview-img    { width: 100%; display: block; max-height: 70vh; object-fit: contain; }
.preview-nombre { padding: 12px 16px; font-weight: 700; font-size: 0.95rem; color: #333; margin: 0; text-align: center; }

/* ─── Responsive ─── */
/* ── Dropdown de acciones ── */
.desktop-actions { display: flex; gap: 6px; align-items: center; }
.mobile-actions { display: none; }
.action-menu-wrap {
  position: relative;
}
.btn-dots {
  width: 32px; height: 32px; border-radius: 8px;
  border: 1px solid #e8e8e8; background: #fff;
  display: flex; align-items: center; justify-content: center;
  color: #888; cursor: pointer; transition: all 0.15s;
}
.btn-dots:hover { background: #f5f5f5; color: #333; border-color: #ccc; }
.action-dropdown {
  position: absolute; right: 0; top: calc(100% + 6px);
  background: #fff; border: 1px solid #e8e8e8;
  border-radius: 10px; box-shadow: 0 4px 16px rgba(0,0,0,0.12);
  min-width: 148px; z-index: 200; overflow: hidden;
}
.action-item {
  width: 100%; display: flex; align-items: center; gap: 9px;
  padding: 10px 14px; background: none; border: none;
  font-size: 0.85rem; font-weight: 500; color: #333;
  cursor: pointer; text-align: left; transition: background 0.12s;
  font-family: inherit;
}
.action-item:hover { background: #f5f5f5; }
.action-item-del { color: #e74c3c; }
.action-item-del:hover { background: #fef2f2; }

@media (max-width: 600px) {
  .badge-3d, .badge-no3d { display: none; }
  .desktop-actions { display: none; }
  .mobile-actions { display: block; }

  /* Modal como bottom sheet en mobile */
  .edit-modal-overlay {
    align-items: flex-end;
    padding: 0;
  }
  .edit-modal {
    max-width: 100%;
    max-height: 92vh;
    border-radius: 20px 20px 0 0;
  }
}
</style>
