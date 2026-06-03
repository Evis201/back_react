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
import CreateProfile from './pages/CreateProfile.jsx'
import SwipePage from './pages/SwipePage.jsx'
import LikedStudents from './pages/LikedStudents.jsx'
import AdminPage from './pages/AdminPage.jsx'


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
        <Route path="/profil/creer" element={<CreateProfile />} />
        <Route path="/swipe" element={<SwipePage />} />
        <Route path="/swipe/liked" element={<LikedStudents />} />
        <Route path="/admin" element={<AdminPage />} />
      </Routes>
    </>
  )
}

export default App
