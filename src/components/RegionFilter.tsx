import React from 'react';
import { Region } from '../types/clinic';

interface RegionFilterProps {
  selectedRegion: string;
  onRegionChange: (region: string) => void;
  selectedCity?: string | null;
  onCitySelect?: (city: string | null) => void;
  regions: Region[];
}

const RegionFilter: React.FC<RegionFilterProps> = ({ selectedRegion, onRegionChange, regions, selectedCity, onCitySelect }) => {
  const regionsToShow = Array.isArray(regions) ? regions.filter(r => r.id !== 'all') : [];
  const activeRegion = regions.find(r => r.id === selectedRegion);

  return (
    <div className="mb-2">
      {/* Regions row */}
      <div className="flex gap-2 justify-start sm:justify-center overflow-x-auto sm:overflow-visible no-scrollbar snap-x snap-mandatory px-1 py-1">
        <button
          onClick={() => onRegionChange('all')}
          className={`px-4 py-2 rounded-full text-sm font-medium transition-colors border flex-shrink-0 snap-start ${
            selectedRegion === 'all'
              ? 'bg-[#182084] text-white border-[#182084]'
              : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-300'
          }`}
        >
          Semua
        </button>
        {regionsToShow.map((region) => (
          <button
            key={region.id}
            onClick={() => onRegionChange(region.id)}
            className={`px-4 py-2 rounded-full text-sm font-medium transition-colors border flex-shrink-0 snap-start ${
              selectedRegion === region.id
                ? 'bg-[#182084] text-white border-[#182084]'
                : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-300'
            }`}
          >
            {region.name}
          </button>
        ))}
      </div>

      {/* Cities row (only when a specific region is selected and has cities) */}
      {selectedRegion !== 'all' && activeRegion && activeRegion.cities && activeRegion.cities.length > 0 && (
        <div className="flex gap-2 justify-start sm:justify-center overflow-x-auto sm:overflow-visible no-scrollbar px-1 py-1 mt-2">
          <button
            onClick={() => onCitySelect && onCitySelect(null)}
            className={`px-3 py-1.5 rounded-full text-xs font-medium transition-colors border flex-shrink-0 ${
              !selectedCity
                ? 'bg-slate-800 text-white border-slate-800'
                : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-300'
            }`}
          >
            Semua kota
          </button>
          {activeRegion.cities.map((city) => (
            <button
              key={city.name}
              onClick={() => onCitySelect && onCitySelect(city.name)}
              className={`px-3 py-1.5 rounded-full text-xs font-medium transition-colors border flex-shrink-0 ${
                selectedCity === city.name
                  ? 'bg-slate-800 text-white border-slate-800'
                  : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-300'
              }`}
              title={`${city.count} klinik`}
            >
              {city.name}
            </button>
          ))}
        </div>
      )}
      {selectedRegion !== 'all' && activeRegion && (!activeRegion.cities || activeRegion.cities.length === 0) && (
        <div className="px-2 mt-2 text-center">
          <span className="inline-block text-xs text-slate-500 bg-slate-100 border border-slate-200 rounded-full px-3 py-1">
            Tidak ada data kota untuk region ini
          </span>
        </div>
      )}
    </div>
  );
}

export default RegionFilter;