import React from 'react';
import { Clinic } from '../types/clinic';
import SearchBar from './SearchBar';
import ClinicCard from './ClinicCard';
import { Menu } from 'lucide-react';

interface SidebarProps {
  clinics: Clinic[];
  searchTerm: string;
  onSearchChange: (value: string) => void;
  selectedClinic: Clinic | null;
  onClinicSelect: (clinic: Clinic) => void;
  loading?: boolean;
  error?: string | null;
}

const Sidebar: React.FC<SidebarProps> = ({
  clinics,
  searchTerm,
  onSearchChange,
  selectedClinic,
  onClinicSelect,
  loading = false,
  error = null,
  
}) => {
  return (
    <div className="bg-white rounded-xl border border-slate-200 shadow h-full flex flex-col">
      <div className="p-4 sm:p-6 border-b border-slate-100">
        <SearchBar
          value={searchTerm}
          onChange={onSearchChange}
          placeholder="Temukan Cabang Terdekat"
        />
      </div>

      {/* Only this section scrolls */}
      <div className="px-4 sm:px-6 py-3 sm:py-4 overflow-y-auto flex-1 min-h-0">
        {error && (
          <div className="mb-3 p-3 rounded-lg border border-red-200 bg-red-50 text-red-700 text-sm">
            {error}
          </div>
        )}

        <div className="space-y-4">
          {loading ? (
            Array.from({ length: 5 }).map((_, i) => (
              <div key={i} className="p-4 rounded-lg border border-gray-200 bg-white animate-pulse">
                <div className="flex gap-4">
                  <div className="w-16 h-16 rounded-lg bg-gray-200" />
                  <div className="flex-1 min-w-0">
                    <div className="h-4 bg-gray-200 rounded w-2/3 mb-3" />
                    <div className="h-3 bg-gray-100 rounded w-full mb-2" />
                    <div className="h-3 bg-gray-100 rounded w-5/6 mb-3" />
                    <div className="flex gap-2 mb-2">
                      <div className="h-5 w-20 bg-blue-100 rounded-full" />
                      <div className="h-5 w-16 bg-blue-100 rounded-full" />
                    </div>
                    <div className="h-8 bg-gray-100 rounded-full w-full" />
                  </div>
                </div>
              </div>
            ))
          ) : clinics.length === 0 ? (
            <div className="text-center py-8">
              <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <Menu className="w-8 h-8 text-gray-400" />
              </div>
              <p className="text-gray-500">Tidak ada klinik yang ditemukan</p>
              <p className="text-sm text-gray-400 mt-1">Coba ubah filter pencarian Anda</p>
            </div>
          ) : (
            clinics.map((clinic) => (
              <ClinicCard
                key={clinic.id}
                clinic={clinic}
                isSelected={selectedClinic?.id === clinic.id}
                onClick={() => onClinicSelect(clinic)}
              />
            ))
          )}
        </div>
      </div>
    </div>
  );
};

export default Sidebar;