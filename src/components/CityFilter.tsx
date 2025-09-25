import React from 'react';

interface CityFilterProps {
  cities: string[];
  selectedCity: string | null;
  onCitySelect: (city: string | null) => void;
}

const CityFilter: React.FC<CityFilterProps> = ({ cities, selectedCity, onCitySelect }) => {
  if (!cities || cities.length === 0) return null;

  return (
    <div className="mt-2">
      <div className="flex gap-2 flex-wrap">
        <button
          onClick={() => onCitySelect(null)}
          className={`px-4 py-2 rounded-full text-sm font-medium transition-colors border ${
            selectedCity === null
              ? 'bg-[#182084] text-white border-[#182084]'
              : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-300'
          }`}
        >
          Semua Kota
        </button>
        {cities.map((city) => (
          <button
            key={city}
            onClick={() => onCitySelect(city)}
            className={`px-4 py-2 rounded-full text-sm font-medium transition-colors border ${
              selectedCity === city
                ? 'bg-[#182084] text-white border-[#182084]'
                : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-300'
            }`}
          >
            {city}
          </button>
        ))}
      </div>
    </div>
  );
};

export default CityFilter;
