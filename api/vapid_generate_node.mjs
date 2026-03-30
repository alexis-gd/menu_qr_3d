// Generador de claves VAPID usando Node.js (sin PHP, sin dependencias externas)
// Uso: node api/vapid_generate_node.mjs
// Requiere Node 16+

const { webcrypto } = await import('crypto')
const { subtle } = webcrypto

const keyPair = await subtle.generateKey(
  { name: 'ECDH', namedCurve: 'P-256' },
  true,
  ['deriveKey']
)

const publicKeyRaw  = await subtle.exportKey('raw', keyPair.publicKey)
const privateKeyJwk = await subtle.exportKey('jwk', keyPair.privateKey)

const toBase64url = (buf) =>
  Buffer.from(buf).toString('base64').replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '')

const publicKey  = toBase64url(publicKeyRaw)
const privateKey = privateKeyJwk.d  // campo 'd' del JWK ya es base64url

console.log('\n=== CLAVES VAPID GENERADAS ===\n')
console.log('vapid_public_key:  ' + publicKey)
console.log('vapid_private_key: ' + privateKey)
console.log('\nCopia estos valores en config.php (sección services)\n')
