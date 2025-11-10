@if(request()->path() === 'case-summary')
    <style>
        /* ==========================================================================
           1. Base & Layout Styles
           ========================================================================== */
        .main-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding: 2rem 1.3rem;
            background-color: #D1D5DB;
        }

        .content-wrapper {
            width: 100%;
            max-width: 56rem;
            margin: 0 auto;
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 6px 10px -2px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        /* ==========================================================================
           2. Component Styles
           ========================================================================== */
        /* Search Components */
        .search-container {
            padding: 1.5rem;
        }

        .search-input {
            width: 100%;
            padding: 1rem;
            color: #1f2937;
            border: 1px solid #D1D5DB;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            outline: none;
            background-repeat: no-repeat;
            background-image: linear-gradient(to right, #3b82f6, #8b5cf6, #06b6d4),
            linear-gradient(to top, #3b82f6, #8b5cf6, #06b6d4),
            linear-gradient(to left, #3b82f6, #8b5cf6, #06b6d4),
            linear-gradient(to bottom, #3b82f6, #8b5cf6, #06b6d4);
            background-position: left bottom,
            right bottom,
            right top,
            left top;
            background-size: 0 2px, 2px 0, 0 2px, 2px 0;
            transition: box-shadow 0.3s ease-in-out;
        }

        .search-input:focus {
            border-color: transparent;
            box-shadow: 0 0 8px 0 rgba(99, 102, 241, 0.4);
            animation: draw-border 0.8s forwards;
        }

        input[type=search]::-webkit-search-cancel-button {
            cursor: pointer;
        }

        .search-results {
            position: absolute;
            z-index: 20;
            width: 100%;
            max-height: 15rem;
            margin-top: 0.25rem;
            overflow-y: auto;
            background-color: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            box-shadow: 0 4px 8px -2px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        }

        .search-result-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #1f2937;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
        }

        .search-result-item:hover {
            background-color: #D8DCE3;
        }

        .search-result-item .material-icons-outlined {
            margin-right: 0.75rem;
            font-size: 1.5rem;
            color: #757575;
        }

        .search-result-item .flex-col {
            margin-left: 0.75rem;
        }

        /* Spinner */
        .spinner-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .spinner {
            width: 4rem;
            height: 4rem;
            border-width: 2px;
            border-style: solid;
            border-color: #f3f4f6;
            border-top-color: #6366f1;
            border-radius: 9999px;
            animation: spin 1s linear infinite;
        }

        /* Empty State */
        .empty-state {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin-top: 3rem;
            color: #757575;
        }

        .empty-state .material-icons-outlined {
            margin-bottom: 1.5rem;
            font-size: 4rem;
        }

        .empty-state p {
            font-size: 1.125rem;
            text-align: center;
        }

        /* Nav Link Effect */
        .nav-link {
            position: relative;
            overflow: hidden;
            padding-bottom: 8px;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4);
            transform: scaleX(0);
            transition: transform 0.3s ease;
            transform-origin: center;
        }

        .nav-link:hover::after {
            transform: scaleX(1);
        }


        /* Proforma Details */
        .proforma-details-container {
            margin-top: 1.5rem;
            padding: 1.5rem;
            overflow: auto;
            background-color: white;
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 4px 8px -2px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        }

        .proforma-details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.25rem;
            color: #1f2937;
        }

        .proforma-details-box {
            position: relative;
            overflow: hidden;
            padding: 1rem;
            background-color: #D1D5DB;
            border: 2px dotted lightgrey;
            border-radius: 0.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .proforma-details-box:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .proforma-details-box:focus-within {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }

        .proforma-details-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .proforma-details-box:hover::before {
            transform: scaleX(1);
        }

        .proforma-details-box .font-medium {
            margin-bottom: 0.75rem;
            font-weight: 600;
        }

        .proforma-details-box .font-medium span {
            margin-right: 0.5rem;
        }

        .proforma-details-box .material-icons-outlined {
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }

        .proforma-details-box:hover .material-icons-outlined {
            transform: scale(1.1);
            color: #3b82f6 !important;
        }

        .proforma-details-box pre {
            margin: 0;
            padding: 0;
            overflow-x: auto;
            white-space: pre-wrap;
            border: none;
            border-radius: 0.5rem;
            background-color: #D1D5DB;
            font-family: monospace;
            font-size: 0.875rem;
            font-weight: 700;
            line-height: 1.4;
        }

        .proforma-details-box .relative {
            overflow: hidden;
            border-radius: 8px;
        }

        .proforma-details-box .bg-blue-500 {
            background: linear-gradient(90deg, #3b82f6, #1d4ed8) !important;
        }

        .proforma-details-box .bg-green-500 {
            background: linear-gradient(90deg, #10b981, #047857) !important;
        }


        /* Status Badges & Dividers */
        .text-green-500 {
            color: #22c55e !important;
        }

        .text-gray-500 {
            color: dimgrey !important;
        }

        .status-badge {
            margin-left: 0.5rem;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 0.375rem;
        }

        .status-badge.approved {
            color: white;
            background-color: #22c55e;
        }

        .status-badge.cancelled {
            color: white;
            background-color: lightcoral;
        }

        .status-badge.settled {
            color: white;
            background-color: dimgrey;
        }

        .status-badge.info {
            font-size: large;
            color: white;
            background-color: #6366f1;
        }

        .status-badge.pending {
            color: white;
            background-color: #facc15;
        }

        .status-badge.text-xl {
            font-size: large !important;
        }

        .status-span {
            grid-column: span 2;
        }

        .divider-attachment {
            padding-top: 0.5rem;
            border-top: 2px dotted #ccc;
        }

        /* Buttons & Pagination */
        .my-dark-class {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            color: #374151;
            background-color: #D1D5DB;
            border: 1px solid #d3d3d3;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .pagination-button {
            padding: 0.5rem 1rem;
            background-color: #e5e7eb;
            border-radius: 0.25rem;
            transition: background-color 0.2s;
        }

        .pagination-button:hover {
            background-color: #d1d5db;
        }

        .pagination-summary {
            display: inline-block;
            margin-left: 1rem;
            font-size: 0.875rem;
            font-weight: 400;
            line-height: 1.5;
            color: #4B5563;
            white-space: nowrap;
            text-align: left;
        }

        .pagination-summary .total-items {
            font-weight: 500;
            color: #6B7280;
        }

        .pagination-summary .page-info {
            font-weight: 500;
            color: #1F2937;
        }

        .pagination-summary .page-info span {
            font-weight: 600;
            color: #6366F1;
        }

        /* Navigation Tabs */
        .nav-tabs {
            margin-top: 1.5rem;
            margin-bottom: 1rem;
        }

        .nav-link {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            color: #374151;
            background-color: #e5e7eb;
            border: 1px solid #d3d3d3;
            border-bottom: none;
            border-radius: 0.5rem 0.5rem 0 0;
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .nav-link:hover {
            background-color: #d1d5db;
        }

        .nav-link.bg-blue-500 {
            color: white;
            background-color: #6366f1;
        }

        .nav-link.bg-blue-500:hover {
            background-color: #6366f1;
        }

        .loader-placeholder {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 200px;
            padding: 2rem;
        }

        .loader-gradient {
            width: 52px;
            height: 52px;
            padding: 4px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 50%;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4);
            animation: loader-spin 1.5s linear infinite;
        }

        .loader-gradient::after {
            content: '';
            width: 100%;
            height: 100%;
            background: #D1D5DB;
            border-radius: 50%;
        }

        body.dark-mode .loader-gradient {
            background: linear-gradient(90deg, #7c3aed, #a855f7, #06b6d4);
        }

        body.dark-mode .loader-gradient::after {
            background: #282828;
        }

        /* Livewire Summary Components */
        .livewire-supplier-summary,
        .livewire-financial-summary {
            margin-top: 1.5rem;
            padding: 1.5rem;
            overflow: hidden;
            background-color: white;
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 4px 8px -2px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        }

        .livewire-supplier-summary h2, .livewire-financial-summary h2,
        .livewire-supplier-summary h3, .livewire-financial-summary h3 {
            margin-bottom: 1rem;
            color: #1f2937;
        }

        .livewire-supplier-summary .text-success,
        .livewire-financial-summary .text-success {
            color: #22c55e;
        }

        .livewire-supplier-summary .text-danger,
        .livewire-financial-summary .text-danger {
            color: lightcoral;
        }

        .livewire-supplier-summary .text-info,
        .livewire-financial-summary .text-info {
            color: #facc15;
        }

        .livewire-supplier-summary .text-subdued,
        .livewire-financial-summary .text-subdued {
            color: dimgrey;
        }

        .livewire-supplier-summary .table-auto,
        .livewire-financial-summary .table-auto {
            width: 100%;
            padding-bottom: 30px;
            overflow: hidden;
            border-collapse: separate;
            border-spacing: 3px;
            border-radius: 1rem;
        }

        .livewire-supplier-summary .table-auto th,
        .livewire-supplier-summary .table-auto td,
        .livewire-financial-summary .table-auto th,
        .livewire-financial-summary .table-auto td {
            background-color: #D1D5DB;
            text-align: left;
        }

        .livewire-supplier-summary .table-auto th,
        .livewire-financial-summary .table-auto th {
            font-weight: bold;
            background-color: #D1D5DB;
        }

        /* ==========================================================================
           3. Utility Classes
           ========================================================================== */
        .insight {
            color: #6366f1 !important;
        }

        .tooltip {
            padding: 6px;
            min-width: 110px;
            color: white;
            background-color: #6466F1;
            border-radius: 5px;
            text-align: center;
        }

        .main-color-complement {
            color: #6466F1;
        }

        .help-cursor {
            cursor: help !important;
        }

        .cursor-not-allowed,
        .disabled {
            cursor: not-allowed !important;
            pointer-events: all !important; /* Note: .disabled has this, might be intentional */
        }

        .enabled {
            cursor: pointer;
        }

        .whitespace-pre {
            white-space: pre;
        }

        /* ==========================================================================
           4. Scrollbar Styles
           ========================================================================== */
        ::-webkit-scrollbar {
            width: 12px;
            height: 8px;
        }

        .overflow-x-auto::-webkit-scrollbar {
            width: 12px;
            height: 4px;
        }

        ::-webkit-scrollbar-track,
        .overflow-x-auto::-webkit-scrollbar-track {
            border-radius: 8px;
            background: transparent;
        }

        ::-webkit-scrollbar-thumb,
        .overflow-x-auto::-webkit-scrollbar-thumb {
            border: 2px solid transparent;
            border-radius: 8px;
            background: #6366f1 !important;
            background-clip: padding-box;
        }

        /* ==========================================================================
           5. Animations
           ========================================================================== */
        @keyframes draw-border {
            0% {
                background-size: 0 2px, 2px 0, 0 2px, 2px 0;
            }
            25% {
                background-size: 100% 2px, 2px 0, 0 2px, 2px 0;
            }
            50% {
                background-size: 100% 2px, 2px 100%, 0 2px, 2px 0;
            }
            75% {
                background-size: 100% 2px, 2px 100%, 100% 2px, 2px 0;
            }
            100% {
                background-size: 100% 2px, 2px 100%, 100% 2px, 2px 100%;
            }
        }

        @keyframes spin {
            0% {
                transform: translate(-50%, -50%) rotate(0deg);
            }
            100% {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        /* ==========================================================================
           6. Media Queries & Responsive Styles
           ========================================================================== */
        @media (min-width: 640px) {
            .proforma-details-grid {
                grid-template-columns: repeat(4, 1fr);
            }

            .status-span {
                grid-column: span 4;
            }
        }

        @media (min-width: 1300px) {
            .proforma-details-grid {
                grid-template-columns: repeat(8, 1fr);
            }

            .status-span {
                grid-column: span 8;
            }
        }

        @media (max-width: 640px) {
            body {
                font-size: small !important;
            }

            .proforma-details-grid {
                gap: 1rem;
            }

            .proforma-details-box {
                padding: 1rem;
            }

            .empty-state > p {
                font-size: 1rem;
            }

            .status-badge {
                font-size: 0.5rem;
            }

            .status-badge.text-xl {
                font-size: medium !important;
            }
        }

        @media print {
            .proforma-details-box {
                border: 1px solid #000;
                box-shadow: none;
                transform: none;
                break-inside: avoid;
            }
        }

        /* ==========================================================================
           7. Dark Mode
           ========================================================================== */
        body.dark-mode {
            color: lightgrey;
            background-color: #121212;
            transition: all 0.5s ease;
        }

        body.dark-mode .main-container {
            background-color: #1e1e1e;
        }

        body.dark-mode .content-wrapper {
            background-color: #282828;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        body.dark-mode .search-input {
            color: lightgrey;
            background-color: #333;
            border-color: #555;
        }

        body.dark-mode .search-input:focus {
            border-color: #7c3aed;
            box-shadow: 0 2px 5px 0 rgba(255, 255, 255, 0.15);
        }

        body.dark-mode .search-results {
            background-color: #333;
            border-color: #555;
        }

        body.dark-mode .search-result-item {
            color: lightgrey;
        }

        body.dark-mode .search-result-item:hover {
            background-color: #444;
        }

        body.dark-mode .search-result-item .material-icons-outlined {
            color: #999;
        }

        body.dark-mode .spinner {
            border-color: #555;
            border-top-color: #7c3aed;
        }

        body.dark-mode .proforma-details-container {
            background-color: #282828;
        }

        body.dark-mode .proforma-details-grid {
            color: lightgrey;
        }

        body.dark-mode .proforma-details-box {
            border-color: #444;
            background: linear-gradient(145deg, #282828 0%, #333333 100%);
        }

        body.dark-mode .proforma-details-box:hover {
            border-color: #555;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
        }

        body.dark-mode .proforma-details-box::before {
            background: linear-gradient(90deg, #7c3aed, #a855f7, #06b6d4);
        }

        body.dark-mode .proforma-details-box:hover .material-icons-outlined {
            color: #7c3aed !important;
        }

        body.dark-mode .proforma-details-box pre {
            color: lightgrey;
            background-color: transparent;
            border: none;
        }

        body.dark-mode .empty-state {
            color: #999;
        }

        body.dark-mode .empty-state .material-icons-outlined {
            color: #777;
        }

        body.dark-mode .my-dark-class {
            color: lightgrey;
            background-color: #282828;
            border-color: #444;
            box-shadow: 0 2px 4px rgba(255, 255, 255, 0.1);
        }

        body.dark-mode .my-dark-class:hover {
            background-color: #333;
        }

        body.dark-mode .pagination-button {
            color: white;
            background-color: #444;
        }

        body.dark-mode .pagination-button:hover {
            background-color: #333;
        }

        body.dark-mode .page-info {
            color: grey;
        }

        body.dark-mode .nav-link {
            color: lightgrey;
            background-color: #333;
            border-color: #555;
        }

        body.dark-mode .nav-link:hover {
            background-color: #444;
        }

        body.dark-mode .nav-link.bg-blue-500 {
            color: white;
            background-color: #7c3aed;
            border-color: #7c3aed;
        }

        body.dark-mode .nav-link.bg-blue-500:hover {
            background-color: #7c3aed;
        }

        body.dark-mode .livewire-supplier-summary,
        body.dark-mode .livewire-financial-summary {
            background-color: #282828;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        body.dark-mode .livewire-supplier-summary h2,
        body.dark-mode .livewire-supplier-summary h3,
        body.dark-mode .livewire-financial-summary h2,
        body.dark-mode .livewire-financial-summary h3 {
            color: lightgrey;
        }

        body.dark-mode .livewire-supplier-summary .table-auto th,
        body.dark-mode .livewire-supplier-summary .table-auto td,
        body.dark-mode .livewire-financial-summary .table-auto th,
        body.dark-mode .livewire-financial-summary .table-auto td {
            background-color: #333;
        }

        body.dark-mode .livewire-supplier-summary .table-auto .border,
        body.dark-mode .livewire-supplier-summary .table-auto td,
        body.dark-mode .livewire-financial-summary .table-auto .border,
        body.dark-mode .livewire-financial-summary .table-auto td {
            color: lightgrey;
            border: none;
        }

        body.dark-mode .livewire-supplier-summary .table-auto th,
        body.dark-mode .livewire-financial-summary .table-auto th {
            background-color: #333;
        }

        body.dark-mode .status-badge.info {
            background-color: #7c3aed !important;
        }

        body.dark-mode .insight {
            color: #7C3AED !important;
        }

        body.dark-mode .tooltip {
            background: #7c3aed;
        }

        body.dark-mode .main-color-complement {
            color: #7c3aed;
        }

        body.dark-mode::-webkit-scrollbar {
            width: 0.5rem;
            height: 0.5rem;
        }

        body.dark-mode .overflow-x-auto::-webkit-scrollbar {
            width: 0.5rem;
            height: 0.25rem;
        }

        body.dark-mode::-webkit-scrollbar-track,
        body.dark-mode .overflow-x-auto::-webkit-scrollbar-track {
            background: #09090B;
        }

        body.dark-mode::-webkit-scrollbar-thumb,
        body.dark-mode .overflow-x-auto::-webkit-scrollbar-thumb {
            background: #7c3aed !important;
            border-radius: 0.25rem;
        }

        body.dark-mode::-webkit-scrollbar-thumb:hover,
        body.dark-mode .overflow-x-auto::-webkit-scrollbar-thumb:hover {
            background-color: #777;
        }
    </style>
@endif
