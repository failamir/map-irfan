import React from 'react';
import { Clinic } from '../types/clinic';
import { MapPin, Phone, Star, Clock } from 'lucide-react';

interface ClinicCardProps {
  clinic: Clinic;
  isSelected: boolean;
  onClick: () => void;
}

const ClinicCard: React.FC<ClinicCardProps> = ({ clinic, isSelected, onClick }) => {
  const manualMaps = clinic.maps && clinic.maps.trim().length > 0 ? clinic.maps.trim() : '';
  const hasCoords = Array.isArray(clinic.coordinates) && clinic.coordinates.length === 2 &&
    Number.isFinite(clinic.coordinates[0]) && Number.isFinite(clinic.coordinates[1]);
  const mapsUrl = manualMaps || (
    hasCoords
      ? `https://www.google.com/maps?q=${clinic.coordinates[0]},${clinic.coordinates[1]}`
      : `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${clinic.name} ${clinic.address}`)}`
  );
  return (
    <div
      className={`p-4 rounded-lg border cursor-pointer transition-all duration-300 hover:shadow-md ${
        isSelected
          ? 'border-orange-300 bg-orange-50 shadow-md'
          : 'border-gray-200 bg-white hover:border-blue-300'
      }`}
      onClick={onClick}
    >
      <div className="flex gap-4">
        <img
          src={clinic.image}
          alt={clinic.name}
          className="w-16 h-16 rounded-lg object-cover flex-shrink-0"
        />
        
        <div className="flex-1 min-w-0">
          <h3 className="font-semibold text-gray-800 mb-1 truncate">
            {clinic.name}
          </h3>
          
          <div className="flex items-start gap-1 mb-2">
            <MapPin className="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" />
            <p className="text-sm text-gray-600 line-clamp-2">
              {clinic.address}
            </p>
          </div>
          
          <div className="flex items-center gap-4 mb-2">
            <div className="flex items-center gap-1">
              <Phone className="w-4 h-4 text-green-600" />
              <span className="text-sm text-gray-700">{clinic.phone}</span>
            </div>
            
            <div className="flex items-center gap-1">
              <Star className="w-4 h-4 text-yellow-500 fill-current" />
              <span className="text-sm font-medium">{clinic.rating}</span>
            </div>
          </div>
          
          <div className="flex flex-wrap gap-1 mb-2">
            {clinic.services.slice(0, 2).map((service) => (
              <span
                key={service}
                className="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full"
              >
                {service}
              </span>
            ))}
            {clinic.services.length > 2 && (
              <span className="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">
                +{clinic.services.length - 2}
              </span>
            )}
          </div>
          
          <div className="flex items-center gap-1 text-xs text-green-600">
            <Clock className="w-3 h-3" />
            <span>Buka 24 jam</span>
          </div>
          
          <div className="mt-3 grid grid-cols-2 gap-2">
            <button
              className={`py-2 px-4 rounded-full text-sm font-medium transition-colors border ${
                isSelected
                  ? 'border-orange-400 text-orange-700 hover:bg-orange-50'
                  : 'border-blue-400 text-blue-700 hover:bg-blue-50'
              }`}
            >
              Explore Treatment
            </button>
            <a
              href={mapsUrl}
              target="_blank"
              rel="noopener noreferrer"
              onClick={(e) => e.stopPropagation()}
              className="py-2 px-4 rounded-full text-sm font-medium transition-colors border border-slate-300 text-slate-700 hover:bg-slate-50 text-center"
              title="Buka di Google Maps"
            >
              Buka Maps
            </a>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ClinicCard;