import React from 'react';
import { regions } from '../data/clinics';

interface RegionFilterProps {
  selectedRegion: string;
  onRegionChange: (region: string) => void;
}

const RegionFilter: React.FC<RegionFilterProps> = ({ selectedRegion, onRegionChange }) => {
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
        {regions.map((region) => (
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
    </div>
  );
};

export default RegionFilter;