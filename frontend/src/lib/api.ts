import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

let csrfFetched = false

export async function ensureCsrfCookie(): Promise<void> {
  if (csrfFetched) return
  await api.get('/sanctum/csrf-cookie')
  csrfFetched = true
}

export default api