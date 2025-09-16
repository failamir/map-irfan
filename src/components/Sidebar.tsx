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
}

const Sidebar: React.FC<SidebarProps> = ({
  clinics,
  searchTerm,
  onSearchChange,
  selectedClinic,
  onClinicSelect,
  
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

      <div className="px-4 sm:px-6 py-4 overflow-y-auto">
        <div className="space-y-4">
          {clinics.length === 0 ? (
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