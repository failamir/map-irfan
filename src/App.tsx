import { useState, useMemo, useEffect } from 'react';
import MapComponent from './components/MapComponent';
import Sidebar from './components/Sidebar';
import RegionFilter from './components/RegionFilter';
import { Clinic, Region } from './types/clinic';
import { CLINICS_ENDPOINT, REGIONS_ENDPOINT } from './config';

function App() {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedRegion, setSelectedRegion] = useState('all');
  const [selectedCity, setSelectedCity] = useState<string | null>(null);
  const [selectedClinic, setSelectedClinic] = useState<Clinic | null>(null);
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [clinicsData, setClinicsData] = useState<Clinic[]>([]);
  const [loading, setLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);
  const [regionsData, setRegionsData] = useState<Region[]>([]);

  // Reset city when region changes
  useEffect(() => {
    setSelectedCity(null);
  }, [selectedRegion]);

  useEffect(() => {
    let cancelled = false;
    const parsePossiblyDirtyJson = (raw: string) => {
      // Trim anything before the first JSON array/object bracket
      const firstBraceIdx = raw.search(/[\[{]/);
      if (firstBraceIdx > 0) {
        console.warn('Sanitizing REST response: stripping leading non-JSON content');
        raw = raw.slice(firstBraceIdx);
      }
      return JSON.parse(raw);
    };
    const withTs = (url: string) => {
      const u = new URL(url);
      u.searchParams.set('_ts', String(Date.now()));
      return u.toString();
    };
    const fetchClinics = async () => {
      setLoading(true);
      setError(null);
      try {
        const res = await fetch(withTs(CLINICS_ENDPOINT), { method: 'GET' });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const text = await res.text();
        const data: Clinic[] = parsePossiblyDirtyJson(text);
        if (!cancelled) setClinicsData(Array.isArray(data) ? data : []);
      } catch (e: any) {
        console.error('Failed to fetch clinics:', e?.message || e);
        if (!cancelled) setError('Gagal memuat data dari WordPress.');
      } finally {
        if (!cancelled) setLoading(false);
      }
    };
    const fetchRegions = async () => {
      try {
        const res = await fetch(withTs(REGIONS_ENDPOINT), { method: 'GET' });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const text = await res.text();
        const data: Region[] = parsePossiblyDirtyJson(text);
        if (!cancelled) setRegionsData(Array.isArray(data) ? data : []);
      } catch (e) {
        console.warn('Failed to fetch regions');
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
      const matchesCity = !selectedCity || clinic.city === selectedCity;
      
      return matchesSearch && matchesRegion && matchesCity;
    });
  }, [searchTerm, selectedRegion, selectedCity, clinicsData]);

  const handleClinicSelect = (clinic: Clinic) => {
    setSelectedClinic(clinic);
  };

  const handleToggleSidebar = () => {
    setSidebarOpen(!sidebarOpen);
  };

  return (
    <div className="fixed inset-0 bg-[#F3F8FF] overflow-hidden">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full min-h-0 flex flex-col py-4 sm:py-6">
        {/* Page Header */}
        {/* <h1 className="text-2xl sm:text-3xl font-bold text-center text-slate-800">
          Temukan Cabang Terdekat di Kotamu
        </h1> */}
        <div className="w-full min-w-0 z-40 bg-[#F3F8FF] supports-[backdrop-filter]:bg-[#F3F8FF]/90 backdrop-blur-md rounded-none sm:rounded-xl py-2">
          <RegionFilter
            selectedRegion={selectedRegion}
            onRegionChange={setSelectedRegion}
            regions={regionsData}
            selectedCity={selectedCity}
            onCitySelect={setSelectedCity}
          />
        </div>

        {/* Mobile map (non-scrolling block) */}
        <div className="block lg:hidden shrink-0 mt-2">
          <div className="h-[260px] sm:h-[300px] bg-white rounded-xl border border-slate-200 shadow overflow-hidden">
            <MapComponent
              clinics={filteredClinics}
              selectedClinic={selectedClinic}
              onClinicSelect={handleClinicSelect}
            />
          </div>
        </div>

        {/* Content fills remaining viewport height */}
        <div className="mt-3 sm:mt-4 flex-1 overflow-hidden grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 items-stretch min-h-0">
          {/* Left: Sidebar Card (Sticky container, list inside scrolls) */}
          <div className="h-full min-h-0">
            <Sidebar
              clinics={filteredClinics}
              searchTerm={searchTerm}
              onSearchChange={setSearchTerm}
              selectedClinic={selectedClinic}
              onClinicSelect={handleClinicSelect}
              loading={loading}
              error={error}
            />
          </div>

          {/* Right: Map Card (fills column height, which is viewport-based) */}
          <div className="hidden lg:block h-full min-h-0 bg-white rounded-xl border border-slate-200 shadow overflow-hidden">
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