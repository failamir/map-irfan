import React from 'react';
import { regions as localRegions } from '../data/clinics';
import { Region } from '../types/clinic';

interface RegionFilterProps {
  selectedRegion: string;
  onRegionChange: (region: string) => void;
  selectedCity?: string | null;
  onCitySelect?: (city: string | null) => void;
  regions?: Region[];
}

const RegionFilter: React.FC<RegionFilterProps> = ({ selectedRegion, onRegionChange, regions: regionsData = localRegions, selectedCity, onCitySelect }) => {
  return (
    <div className="mb-2">
      {/* Horizontal scroll on mobile, wrap on larger screens */}
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
        {regionsData.map((region) => (
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

      {/* City chips (only for a specific region) */}
      {selectedRegion !== 'all' && selectedCity !== undefined && onCitySelect && regionsData.find(r => r.id === selectedRegion) && (
        <div className="mt-3">
          <div className="flex flex-wrap gap-2">
            <button
              onClick={() => onCitySelect(null)}
              className={`px-3 py-1.5 rounded-full text-xs border flex-shrink-0 ${
                selectedCity === null ? 'bg-blue-100 text-blue-700 border-blue-300' : 'bg-white text-slate-600 hover:bg-slate-50 border-slate-200'
              }`}
            >
              Semua ({selectedRegionData.clinic_count})
            </button>
            {selectedRegionData.cities.map((city) => (
              <button
                key={city.name}
                onClick={() => onCitySelect(city.name)}
                className={`px-3 py-1.5 rounded-full text-xs border flex-shrink-0 ${
                  selectedCity === city.name ? 'bg-blue-100 text-blue-700 border-blue-300' : 'bg-white text-slate-600 hover:bg-slate-50 border-slate-200'
                }`}
              >
                {city.name} ({city.count})
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

export default RegionFilter;