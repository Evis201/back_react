import Navbar from './components/Navbar.jsx'
import './App.css'
import { Routes, Route } from 'react-router-dom'
import Login from './pages/login.jsx'
import Signup from './pages/signup.jsx'
import Studentslist from './pages/Studentslist.jsx'
import StudentDetail from './pages/StudentDetail.jsx'
import Home from './pages/Home.jsx'
import Offers from './pages/offers.jsx'
import OfferDetail from './pages/offersdetail.jsx'


function App() {

  return (
    <>
      <Navbar />
      <Routes>
        <Route path="/" element={<Home />} />
        <Route path="/info" element={<h1>Info</h1>} />
        <Route path="/eleves" element={<Studentslist />} />
        <Route path="/eleves/:id" element={<StudentDetail />} />
        <Route path="/offers" element={<Offers />} />
        <Route path="/offers/:id" element={<OfferDetail />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Signup />} />
      </Routes>
    </>
  )
}

export default App
