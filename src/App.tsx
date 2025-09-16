import { useState, useMemo } from 'react';
import MapComponent from './components/MapComponent';
import Sidebar from './components/Sidebar';
import RegionFilter from './components/RegionFilter';
import { clinics } from './data/clinics';
import { Clinic } from './types/clinic';

function App() {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedRegion, setSelectedRegion] = useState('all');
  const [selectedClinic, setSelectedClinic] = useState<Clinic | null>(null);
  const [sidebarOpen, setSidebarOpen] = useState(false);

  const filteredClinics = useMemo(() => {
    return clinics.filter((clinic) => {
      const matchesSearch = searchTerm === '' || 
        clinic.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        clinic.city.toLowerCase().includes(searchTerm.toLowerCase()) ||
        clinic.address.toLowerCase().includes(searchTerm.toLowerCase());
      
      const matchesRegion = selectedRegion === 'all' || clinic.region === selectedRegion;
      
      return matchesSearch && matchesRegion;
    });
  }, [searchTerm, selectedRegion]);

  const handleClinicSelect = (clinic: Clinic) => {
    setSelectedClinic(clinic);
  };

  const handleToggleSidebar = () => {
    setSidebarOpen(!sidebarOpen);
  };

  return (
    <div className="min-h-screen bg-slate-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        {/* Page Header */}
        <h1 className="text-3xl sm:text-4xl font-bold text-center text-slate-800">Temukan Cabang Terdekat di Kotamu</h1>
        <div className="mt-6 flex justify-center">
          <RegionFilter
            selectedRegion={selectedRegion}
            onRegionChange={setSelectedRegion}
          />
        </div>

        {/* Content */}
        <div className="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
          {/* Left: Sidebar Card */}
          <div className="w-full h-[520px] sm:h-[560px] lg:h-[620px]">
            <Sidebar
              clinics={filteredClinics}
              searchTerm={searchTerm}
              onSearchChange={setSearchTerm}
              selectedClinic={selectedClinic}
              onClinicSelect={handleClinicSelect}
            />
          </div>

          {/* Right: Map Card */}
          <div className="h-[520px] sm:h-[560px] lg:h-[620px] bg-white rounded-xl border border-slate-200 shadow">
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