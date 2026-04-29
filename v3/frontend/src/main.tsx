import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './styles/v2-app.css'
import './styles/v3-tweak.css'
import './index.css'
import App from './App.tsx'

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <App />
  </StrictMode>,
)
