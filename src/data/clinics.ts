import { Clinic, Region } from '../types/clinic';

export const regions: Region[] = [
  {
    id: 'jabodetabek',
    name: 'Jabodetabek',
    cities: ['Jakarta', 'Bogor', 'Depok', 'Tangerang', 'Bekasi']
  },
  {
    id: 'jawa',
    name: 'Jawa',
    cities: ['Yogyakarta', 'Surabaya', 'Bandung', 'Semarang']
  },
  {
    id: 'sumatera',
    name: 'Sumatera',
    cities: ['Medan', 'Palembang', 'Pekanbaru', 'Padang']
  },
  {
    id: 'bali',
    name: 'Bali',
    cities: ['Denpasar', 'Ubud', 'Sanur', 'Kuta']
  },
  {
    id: 'kalimantan',
    name: 'Kalimantan',
    cities: ['Banjarmasin', 'Pontianak', 'Balikpapan', 'Samarinda']
  },
  {
    id: 'sulawesi',
    name: 'Sulawesi',
    cities: ['Makassar', 'Manado', 'Palu', 'Kendari']
  }
];

export const clinics: Clinic[] = [
  {
    id: '1',
    name: 'Sozo Skin Clinic Yogyakarta',
    address: 'Jl. Pangeran Diponegoro No.58, Gondangjan, Kec. Jetis, Kota Yogyakarta, Daerah Istimewa Yogyakarta 55233',
    city: 'Yogyakarta',
    region: 'jawa',
    phone: '(0274) 561234',
    coordinates: [-7.783, 110.367],
    services: ['Perawatan Kulit', 'Konsultasi Dermatologi', 'Laser Treatment'],
    rating: 4.8,
    image: 'https://images.pexels.com/photos/4989141/pexels-photo-4989141.jpeg?auto=compress&cs=tinysrgb&w=400'
  },
  {
    id: '2',
    name: 'Jakarta Timur - Duren Sawit',
    address: 'Jl. Raden Inten II No.66, RW.7, Duren Sawit, Kec. Duren Sawit, Kota Jakarta Timur, Daerah Khusus',
    city: 'Jakarta',
    region: 'jabodetabek',
    phone: '(021) 8611234',
    coordinates: [-6.2088, 106.9456],
    services: ['Konsultasi Umum', 'Laboratorium', 'Farmasi'],
    rating: 4.6,
    image: 'https://images.pexels.com/photos/4021775/pexels-photo-4021775.jpeg?auto=compress&cs=tinysrgb&w=400'
  },
  {
    id: '3',
    name: 'Jakarta Utara - Kelapa Gading',
    address: 'Jl. Boulevard Artha Gading, Rukan Niaga Artha Gading Blok B No 11, Jakarta Utara',
    city: 'Jakarta',
    region: 'jabodetabek',
    phone: '(021) 4585123',
    coordinates: [-6.1564, 106.9097],
    services: ['Konsultasi Spesialis', 'Medical Check-up', 'Fisioterapi'],
    rating: 4.7,
    image: 'https://images.pexels.com/photos/4386467/pexels-photo-4386467.jpeg?auto=compress&cs=tinysrgb&w=400'
  },
  {
    id: '4',
    name: 'Jakarta Barat - Kemanggisan',
    address: 'Jl. Kemanggisan Raya No.42, RT.5/RW.8, Kemanggisan, Kec. Palmerah, Kota Jakarta Barat',
    city: 'Jakarta',
    region: 'jabodetabek',
    phone: '(021) 5345678',
    coordinates: [-6.1951, 106.7634],
    services: ['Gigi dan Mulut', 'Konsultasi Umum', 'Vaksinasi'],
    rating: 4.5,
    image: 'https://images.pexels.com/photos/4989141/pexels-photo-4989141.jpeg?auto=compress&cs=tinysrgb&w=400'
  },
  {
    id: '5',
    name: 'Surabaya Medical Center',
    address: 'Jl. Ahmad Yani No.15, Wonokromo, Kec. Wonokromo, Kota Surabaya, Jawa Timur',
    city: 'Surabaya',
    region: 'jawa',
    phone: '(031) 8291234',
    coordinates: [-7.2575, 112.7521],
    services: ['Kardiologi', 'Neurologi', 'Onkologi'],
    rating: 4.9,
    image: 'https://images.pexels.com/photos/4021775/pexels-photo-4021775.jpeg?auto=compress&cs=tinysrgb&w=400'
  },
  {
    id: '6',
    name: 'Medan Prima Clinic',
    address: 'Jl. Gatot Subroto No.88, Sei Agul, Kec. Medan Barat, Kota Medan, Sumatera Utara',
    city: 'Medan',
    region: 'sumatera',
    phone: '(061) 7341234',
    coordinates: [3.5952, 98.6722],
    services: ['Penyakit Dalam', 'Bedah Umum', 'Radiologi'],
    rating: 4.6,
    image: 'https://images.pexels.com/photos/4386467/pexels-photo-4386467.jpeg?auto=compress&cs=tinysrgb&w=400'
  },
  {
    id: '7',
    name: 'Denpasar Health Center',
    address: 'Jl. Diponegoro No.125, Dauh Puri Klod, Kec. Denpasar Barat, Kota Denpasar, Bali',
    city: 'Denpasar',
    region: 'bali',
    phone: '(0361) 234567',
    coordinates: [-8.6500, 115.2167],
    services: ['Mata', 'THT', 'Kulit dan Kelamin'],
    rating: 4.7,
    image: 'https://images.pexels.com/photos/4989141/pexels-photo-4989141.jpeg?auto=compress&cs=tinysrgb&w=400'
  },
  {
    id: '8',
    name: 'Banjarmasin Medical Plaza',
    address: 'Jl. Ahmad Yani Km.5, Pemurus Luar, Kec. Banjarmasin Timur, Kota Banjarmasin, Kalimantan Selatan',
    city: 'Banjarmasin',
    region: 'kalimantan',
    phone: '(0511) 456789',
    coordinates: [-3.3194, 114.5906],
    services: ['Anak', 'Kandungan', 'Anestesi'],
    rating: 4.4,
    image: 'https://images.pexels.com/photos/4021775/pexels-photo-4021775.jpeg?auto=compress&cs=tinysrgb&w=400'
  },
  {
    id: '9',
    name: 'Makassar Central Hospital',
    address: 'Jl. Perintis Kemerdekaan No.10, Tamalate, Kec. Tamalate, Kota Makassar, Sulawesi Selatan',
    city: 'Makassar',
    region: 'sulawesi',
    phone: '(0411) 567890',
    coordinates: [-5.1477, 119.4327],
    services: ['ICU', 'Emergency', 'Rehabilitasi Medik'],
    rating: 4.8,
    image: 'https://images.pexels.com/photos/4386467/pexels-photo-4386467.jpeg?auto=compress&cs=tinysrgb&w=400'
  }
];