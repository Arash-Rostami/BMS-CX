@if(request()->path() === 'case-summary')
    <style>
        /* Base Layout */
        .main-container {
            background-color: #D1D5DB;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding: 2rem;
        }

        .content-wrapper {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 6px 10px -2px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            margin: 0 auto;
            max-width: 56rem;
            width: 100%;
        }

        /* Search Components */
        .search-container {
            padding: 1.5rem;
        }

        .search-input {
            border: 2px solid #D1D5DB;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            color: #1f2937;
            outline: none;
            padding: 1rem;
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            width: 100%;
        }

        input[type=search]::-webkit-search-cancel-button {
            cursor: pointer;
        }

        .search-input:focus {
            border-color: #6366f1;
            box-shadow: 0 2px 5px 0 rgba(0, 0, 0, 0.15);
            ring: 0;
        }

        .insight {
            color: #6366f1 !important;
        }

        .search-results {
            background-color: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            box-shadow: 0 4px 8px -2px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            margin-top: 0.25rem;
            max-height: 15rem;
            overflow-y: auto;
            position: absolute;
            width: 100%;
            z-index: 20;
        }

        .search-result-item {
            align-items: center;
            color: #1f2937;
            cursor: pointer;
            display: flex;
            padding: 0.75rem 1rem;
            transition: background-color 0.2s ease-in-out;
        }

        .search-result-item:hover {
            background-color: #D8DCE3;
        }

        .search-result-item .material-icons-outlined {
            color: #757575;
            font-size: 1.5rem;
            margin-right: 0.75rem;
        }

        .search-result-item .flex-col {
            margin-left: 0.75rem;
        }

        /* Spinner */
        .spinner-container {
            left: 50%;
            position: absolute;
            top: 50%;
            transform: translate(-50%, -50%);
        }

        .spinner {
            animation: spin 1s linear infinite;
            border-color: #f3f4f6;
            border-radius: 9999px;
            border-style: solid;
            border-top-color: #6366f1;
            border-width: 2px;
            height: 4rem;
            width: 4rem;
        }

        /* Empty State */
        .empty-state {
            align-items: center;
            color: #757575;
            display: flex;
            flex-direction: column;
            justify-content: center;
            margin-top: 3rem;
        }

        .empty-state .material-icons-outlined {
            font-size: 4rem;
            margin-bottom: 1.5rem;
        }

        .empty-state p {
            font-size: 1.125rem;
            text-align: center;
        }

        /* Proforma Details */
        .proforma-details-container {
            background-color: white;
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 4px 8px -2px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            margin-top: 1.5rem;
            overflow: hidden;
            padding: 1.5rem;
        }

        .proforma-details-grid {
            color: #1f2937;
            display: grid;
            gap: 0.25rem;
            grid-template-columns: repeat(2, 1fr);
        }

        .proforma-details-box {
            background-color: #D1D5DB;
            border: 2px dotted lightgrey;
            border-radius: 0.5rem;
            padding: 1rem;
        }

        .proforma-details-box pre {
            background-color: #D1D5DB;
            border-radius: 0.5rem;
            font-family: monospace;
            font-size: 0.875rem;
            margin: 0;
            overflow-x: auto;
            padding: 1rem;
            white-space: pre-wrap;
        }

        .proforma-details-box .font-medium span {
            margin-right: 0.5rem;
        }

        .status-badge {
            border-radius: 0.375rem;
            font-size: 0.75rem;
            margin-left: 0.5rem;
            padding: 0.25rem 0.5rem;
        }

        .status-badge.approved {
            background-color: #22c55e;
            color: white;
        }

        .status-badge.cancelled {
            background-color: lightcoral;
            color: white;
        }

        .status-badge.settled {
            background-color: dimgrey;
            color: white;
        }

        .status-badge.info {
            background-color: #6366f1;
            color: white;
            font-size: large;
        }

        .status-badge.pending {
            background-color: #facc15;
            color: white;
        }

        .status-span {
            grid-column: span 2;
        }

        .divider-attachment {
            border-top: 2px dotted #ccc;
            padding-top: 0.5rem;
        }

        .status-badge.text-xl {
            font-size: large !important;
        }

        /* Button Style */
        .my-dark-class {
            background-color: #D1D5DB;
            border: 1px solid #d3d3d3;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            color: #374151;
            cursor: pointer;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }

        .pagination-button {
            background-color: #e5e7eb;
            border-radius: 0.25rem;
            padding: 0.5rem 1rem;
            transition: background-color 0.2s;
        }

        .pagination-button:hover {
            background-color: #d1d5db;
        }

        .disabled {
            cursor: not-allowed;
            pointer-events: all !important;
        }

        .enabled {
            cursor: pointer;
        }

        .pagination-summary {
            color: #4B5563;
            display: inline-block;
            font-size: 0.875rem;
            font-weight: 400;
            line-height: 1.5;
            margin-left: 1rem;
            text-align: left;
            white-space: nowrap;
        }

        .pagination-summary .total-items {
            color: #6B7280;
            font-weight: 500;
        }

        .pagination-summary .page-info {
            color: #1F2937;
            font-weight: 500;
        }

        .pagination-summary .page-info span {
            color: #6366F1;
            font-weight: 600;
        }

        .nav-tabs {
            margin-bottom: 1rem;
            margin-top: 1.5rem;
        }

        .nav-link {
            background-color: #e5e7eb;
            border: 1px solid #d3d3d3;
            border-bottom: none;
            border-radius: 0.5rem 0.5rem 0 0;
            color: #374151;
            cursor: pointer;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .nav-link:hover {
            background-color: #d1d5db;
        }

        .nav-link.bg-blue-500 {
            background-color: #6366f1;
            color: white;
        }

        .nav-link.bg-blue-500:hover {
            background-color: #6366f1;
        }

        .livewire-supplier-summary, .livewire-financial-summary {
            background-color: white;
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 4px 8px -2px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            margin-top: 1.5rem;
            overflow: hidden;
            padding: 1.5rem;
        }

        .livewire-supplier-summary h2, .livewire-financial-summary h2,
        .livewire-supplier-summary h3, .livewire-financial-summary h3 {
            color: #1f2937;
            margin-bottom: 1rem;
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
            border-collapse: separate;
            border-radius: 1rem;
            border-spacing: 3px;
            overflow: hidden;
            padding-bottom: 30px;
            width: 100%;
        }


        .livewire-supplier-summary .table-auto th,
        .livewire-supplier-summary .table-auto td,
        .livewire-financial-summary .table-auto th,
        .livewire-financial-summary .table-auto td {
            background-color: #D1D5DB;
            text-align: left;
        }

        .dark-mode .livewire-supplier-summary .table-auto th,
        .dark-mode .livewire-supplier-summary .table-auto td,
        .dark-mode .livewire-financial-summary .table-auto th,
        .dark-mode .livewire-financial-summary .table-auto td {
            background-color: #333;
        }

        .livewire-supplier-summary .table-auto th,
        .livewire-financial-summary .table-auto th {
            background-color: #D1D5DB;
            font-weight: bold;
        }


        .help-cursor {
            cursor: help !important;
        }

        .tooltip {
            background-color: #6466F1;
            min-width: 110px;
            text-align: center;
            padding: 6px;
            border-radius: 5%;
        }

        .cursor-not-allowed {
            cursor: not-allowed !important;
        }

        .whitespace-pre {
            white-space: pre;
        }

        .main-color-complement {
            color: #6466F1
        }

        ::-webkit-scrollbar {
            height: 8px;
            width: 12px;
        }

        ::-webkit-scrollbar-track { /* SCROLL RAIL */
            box-shadow: inset 0 0 0 rgba(0, 0, 0, 0.0);
            border-radius: 8px;
        }

        ::-webkit-scrollbar-thumb { /* SCROLLER */
            background: #6366f1;
            background-clip: padding-box;
            border: 2px solid transparent;
            border-radius: 8px;
        }

        @keyframes spin {
            0% {
                transform: translate(-50%, -50%) rotate(0deg);
            }
            100% {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }


        /* Media Queries */
        @media (min-width: 640px) {
            .proforma-details-grid {
                grid-template-columns: repeat(4, 1fr);
            }

            .status-span {
                grid-column: span 4;
            }
        }

        @media (min-width: 768px) {
            .proforma-details-grid {
                grid-template-columns: repeat(6, 1fr);
            }

            .status-span {
                grid-column: span 6;
            }
        }

        @media (min-width: 1024px) {
            .proforma-details-grid {
                grid-template-columns: repeat(8, 1fr);
            }

            .status-span {
                grid-column: span 8;
            }
        }

        /* Dark Mode */
        body.dark-mode {
            background-color: #121212;
            color: lightgrey;
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
            background-color: #333;
            border-color: #555;
            color: lightgrey;
        }

        body.dark-mode .status-badge.info {
            background-color: #7c3aed !important;
        }

        body.dark-mode .insight {
            color: #7C3AED !important;
            font-size: large;
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
            background-color: #333;
            border: none;
        }

        body.dark-mode .proforma-details-box pre {
            background-color: transparent;
            border: none;
            color: lightgrey;
        }

        body.dark-mode .empty-state {
            color: #999;
        }

        body.dark-mode .empty-state .material-icons-outlined {
            color: #777;
        }

        body.dark-mode ::-webkit-scrollbar {
            width: 0.5rem;
        }

        body.dark-mode ::-webkit-scrollbar-thumb {
            background-color: #555;
            border-radius: 0.25rem;
        }

        body.dark-mode ::-webkit-scrollbar-thumb:hover {
            background-color: #777;
        }

        body.dark-mode ::-webkit-scrollbar-track {
            background-color: #222;
        }

        body.dark-mode .my-dark-class {
            background-color: #282828;
            border-color: #444;
            box-shadow: 0 2px 4px rgba(255, 255, 255, 0.1);
            color: lightgrey;
        }

        body.dark-mode .my-dark-class:hover {
            background-color: #333;
        }

        body.dark-mode .pagination-button {
            background-color: #444;
            color: white
        }

        body.dark-mode .pagination-button:hover {
            background-color: #333;
        }

        body.dark-mode .page-info {
            color: grey;
        }


        body.dark-mode .nav-link {
            background-color: #333;
            border-color: #555;
            color: lightgrey;
        }

        body.dark-mode .nav-link:hover {
            background-color: #444;
        }

        body.dark-mode .nav-link.bg-blue-500 {
            background-color: #7c3aed;
            border-color: #7c3aed;
            color: white;
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


        body.dark-mode .livewire-supplier-summary .table-auto .border,
        body.dark-mode .livewire-supplier-summary .table-auto td,
        body.dark-mode .livewire-financial-summary .table-auto .border,
        body.dark-mode .livewire-financial-summary .table-auto td {
            border: none;
            color: lightgrey;
        }

        body.dark-mode .livewire-supplier-summary .table-auto th,
        body.dark-mode .livewire-financial-summary .table-auto th {
            background-color: #333;
        }

        body.dark-mode .livewire-supplier-summary .status-badge.approved,
        body.dark-mode .livewire-financial-summary .status-badge.approved {
            background-color: #22c55e;
            color: white;
        }

        body.dark-mode .livewire-supplier-summary .status-badge.cancelled,
        body.dark-mode .livewire-financial-summary .status-badge.cancelled {
            background-color: lightcoral;
            color: white;
        }

        body.dark-mode .livewire-supplier-summary .status-badge.settled,
        body.dark-mode .livewire-financial-summary .status-badge.settled {
            background-color: dimgrey;
            color: white;
        }

        body.dark-mode::-webkit-scrollbar-track {
            background: #09090B;
        }

        body.dark-mode::-webkit-scrollbar-thumb {
            background: #7c3aed;
        }

        body.dark-mode .tooltip {
            background: #7c3aed;
        }

        body.dark-mode .tooltip {
            background: #7c3aed;
        }

        body.dark-mode .main-color-complement {
            color: #7c3aed
        }
    </style>
@endif
