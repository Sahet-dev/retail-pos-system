import axios from 'axios'

const apiClient = axios.create({
    baseURL: 'http://127.0.0.1:8000',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json'
    },
    withCredentials: true
})

// Optional: response error handling
apiClient.interceptors.response.use(
    response => response,
    error => {
        console.error('API error:', error.response || error)
        return Promise.reject(error)
    }
)

export default apiClient
