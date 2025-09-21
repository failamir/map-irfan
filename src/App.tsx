import { useState, useMemo, useEffect } from 'react';
import MapComponent from './components/MapComponent';
import Sidebar from './components/Sidebar';
import RegionFilter from './components/RegionFilter';
import { clinics as localClinics, regions as localRegions } from './data/clinics';
import { Clinic, Region } from './types/clinic';
import { CLINICS_ENDPOINT, REGIONS_ENDPOINT } from './config';

function App() {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedRegion, setSelectedRegion] = useState('all');
  const [selectedClinic, setSelectedClinic] = useState<Clinic | null>(null);
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [clinicsData, setClinicsData] = useState<Clinic[]>(localClinics);
  const [loading, setLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);
  const [regionsData, setRegionsData] = useState<Region[]>(localRegions);

  useEffect(() => {
    let cancelled = false;
    const fetchClinics = async () => {
      setLoading(true);
      setError(null);
      try {
        const res = await fetch(CLINICS_ENDPOINT, { method: 'GET' });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data: Clinic[] = await res.json();
        if (!cancelled && Array.isArray(data) && data.length) {
          setClinicsData(data);
        }
      } catch (e: any) {
        console.warn('Falling back to local clinics data:', e?.message || e);
        if (!cancelled) setError('Gagal memuat data dari WordPress, menggunakan data lokal.');
      } finally {
        if (!cancelled) setLoading(false);
      }
    };
    const fetchRegions = async () => {
      try {
        const res = await fetch(REGIONS_ENDPOINT, { method: 'GET' });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data: Region[] = await res.json();
        if (!cancelled && Array.isArray(data) && data.length) {
          setRegionsData(data);
        }
      } catch (e) {
        console.warn('Using local regions fallback');
      }
    };
    fetchClinics();
    fetchRegions();
    return () => { cancelled = true; };
  }, []);

  const filteredClinics = useMemo(() => {
    return clinicsData.filter((clinic) => {
      const matchesSearch = searchTerm === '' || 
        clinic.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        clinic.city.toLowerCase().includes(searchTerm.toLowerCase()) ||
        clinic.address.toLowerCase().includes(searchTerm.toLowerCase());
      
      const matchesRegion = selectedRegion === 'all' || clinic.region === selectedRegion;
      
      return matchesSearch && matchesRegion;
    });
  }, [searchTerm, selectedRegion, clinicsData]);

  const handleClinicSelect = (clinic: Clinic) => {
    setSelectedClinic(clinic);
  };

  const handleToggleSidebar = () => {
    setSidebarOpen(!sidebarOpen);
  };

  return (
    <div className="min-h-screen bg-[#F3F8FF] overflow-x-hidden">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
        {/* Page Header */}
        {/* <h1 className="text-2xl sm:text-3xl font-bold text-center text-slate-800">
          Temukan Cabang Terdekat di Kotamu
        </h1> */}
        <div className="mt-4 sm:mt-6 w-full min-w-0">
          <RegionFilter
            selectedRegion={selectedRegion}
            onRegionChange={setSelectedRegion}
            regions={regionsData}
          />
        </div>

        {/* Content */}
        <div className="mt-4 sm:mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 items-start">
          {/* Left: Sidebar Card */}
          <div className="w-full h-auto lg:h-[620px]">
            <Sidebar
              clinics={filteredClinics}
              searchTerm={searchTerm}
              onSearchChange={setSearchTerm}
              selectedClinic={selectedClinic}
              onClinicSelect={handleClinicSelect}
            />
          </div>

          {/* Right: Map Card */}
          <div className="h-[300px] sm:h-[360px] lg:h-[620px] bg-white rounded-xl border border-slate-200 shadow">
            <MapComponent
              clinics={filteredClinics}
              selectedClinic={selectedClinic}
              onClinicSelect={handleClinicSelect}
            />
          </div>
        </div>
      </div>
    </div>
  );
}

export default App;