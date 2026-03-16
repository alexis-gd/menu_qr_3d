import { ref } from 'vue'

export function useUpload() {
  const uploadToast = ref(null)
  // { texto: string, progreso: 0..100 }

  function xhrUpload(url, formData, texto) {
    uploadToast.value = { texto, progreso: 0 }

    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest()
      xhr.open('POST', url)
      xhr.withCredentials = true
      xhr.upload.onprogress = (e) => {
        if (e.lengthComputable) {
          uploadToast.value.progreso = Math.round((e.loaded / e.total) * 100)
        }
      }
      xhr.onload = () => {
        uploadToast.value = null
        if (xhr.status >= 200 && xhr.status < 300) {
          try { resolve(JSON.parse(xhr.responseText)) }
          catch { reject(new Error('Respuesta inválida del servidor')) }
        } else {
          try { reject(new Error(JSON.parse(xhr.responseText).error || 'Error al subir')) }
          catch { reject(new Error(`Error ${xhr.status}`)) }
        }
      }
      xhr.onerror = () => {
        uploadToast.value = null
        reject(new Error('Error de red'))
      }
      xhr.send(formData)
    })
  }

  return { uploadToast, xhrUpload }
}
