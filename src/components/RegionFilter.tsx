import React from 'react';
import { regions } from '../data/clinics';

interface RegionFilterProps {
  selectedRegion: string;
  onRegionChange: (region: string) => void;
}

const RegionFilter: React.FC<RegionFilterProps> = ({ selectedRegion, onRegionChange }) => {
  return (
    <div className="mb-2">
      <div className="flex flex-wrap gap-2 justify-center">
        <button
          onClick={() => onRegionChange('all')}
          className={`px-4 py-2 rounded-full text-sm font-medium transition-colors border ${
            selectedRegion === 'all'
              ? 'bg-blue-600 text-white border-blue-600'
              : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-300'
          }`}
        >
          Semua
        </button>
        {regions.map((region) => (
          <button
            key={region.id}
            onClick={() => onRegionChange(region.id)}
            className={`px-4 py-2 rounded-full text-sm font-medium transition-colors border ${
              selectedRegion === region.id
                ? 'bg-blue-600 text-white border-blue-600'
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