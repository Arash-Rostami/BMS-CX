@if(request()->path() === 'case-summary')
    <style>
        /* Base Layout */

        .main-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #D1D5DB;
            padding: 2rem;
        }

        .content-wrapper {
            width: 100%;
            max-width: 56rem;
            margin: 0 auto;
            background-color: white;
            box-shadow: 0 6px 10px -2px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-radius: 0.75rem;
        }

        /* Search Components */
        .search-container {
            padding: 1.5rem;
        }

        .search-input {
            width: 100%;
            border: 2px solid #D1D5DB;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            padding: 1rem;
            outline: none;
            color: #1f2937;
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
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
            position: absolute;
            z-index: 20;
            width: 100%;
            max-height: 15rem;
            background-color: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            margin-top: 0.25rem;
            box-shadow: 0 4px 8px -2px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            overflow-y: auto;
        }

        .search-result-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
            color: #1f2937;
            display: flex;
            align-items: center;
        }

        .search-result-item:hover {
            background-color: #D8DCE3;
        }

        .search-result-item .material-icons-outlined {
            font-size: 1.5rem;
            color: #757575;
            margin-right: 0.75rem;
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
            animation: spin 1s linear infinite;
            border-radius: 9999px;
            height: 4rem;
            width: 4rem;
            border-width: 2px;
            border-style: solid;
            border-color: #f3f4f6;
            border-top-color: #6366f1;
        }

        @keyframes spin {
            0% {
                transform: translate(-50%, -50%) rotate(0deg);
            }
            100% {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        /* Empty State */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-top: 3rem;
            color: #757575;
        }

        .empty-state .material-icons-outlined {
            font-size: 4rem;
            margin-bottom: 1.5rem;
        }

        .empty-state p {
            text-align: center;
            font-size: 1.125rem;
        }

        /* Proforma Details */
        .proforma-details-container {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 4px 8px -2px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            background-color: white;
            margin-top: 1.5rem;
            overflow: hidden;
        }

        .proforma-details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.25rem;
            color: #1f2937;
        }

        .proforma-details-box {
            background-color: #D1D5DB;
            border-radius: 0.5rem;
            padding: 1rem;
            border: 2px dotted lightgrey;
        }

        .proforma-details-box pre {
            background-color: #D1D5DB;
            border-radius: 0.5rem;
            padding: 1rem;
            overflow-x: auto;
            white-space: pre-wrap;
            font-family: monospace;
            font-size: 0.875rem;
            margin: 0;
        }

        .proforma-details-box .font-medium span {
            margin-right: 0.5rem;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }

        .status-badge.approved {
            background-color: #22c55e;
            color: white;
        }

        .status-badge.cancelled {
            background-color: lightcoral;
            color: white;
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

        /* Button Style */
        .my-dark-class {
            background-color: #D1D5DB;
            border: 1px solid #d3d3d3;
            color: #374151;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
            color: lightgrey;
            border: none;
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
            color: lightgrey;
            box-shadow: 0 2px 4px rgba(255, 255, 255, 0.1);
        }

        body.dark-mode .my-dark-class:hover {
            background-color: #333;
        }

        .nav-tabs {
            margin-top: 1.5rem;
            margin-bottom: 1rem;
        }

        .nav-link {
            background-color: #e5e7eb;
            color: #374151;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem 0.5rem 0 0;
            font-weight: 500;
            transition: background-color 0.3s ease, color 0.3s ease;
            cursor: pointer;
            border: 1px solid #d3d3d3;
            border-bottom: none;
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

        .livewire-supplier-summary {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 4px 8px -2px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            background-color: white;
            margin-top: 1.5rem;
            overflow: hidden;
        }

        .livewire-supplier-summary h2,
        .livewire-supplier-summary h3 {
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .livewire-supplier-summary .text-success {
            color: #22c55e;
        }

        .livewire-supplier-summary .text-danger {
            color: lightcoral;
        }

        .livewire-supplier-summary .text-info {
            color: #facc15;
        }

        .livewire-supplier-summary .text-subdued {
            color: dimgrey;
        }

        .livewire-supplier-summary .table-auto {
            width: 100%;
            border-collapse: separate;
            border-spacing: 3px;
            border-radius: 1rem;
            overflow: hidden;
        }


        .livewire-supplier-summary .table-auto th,
        .livewire-supplier-summary .table-auto td {
            background-color: #D1D5DB;
            text-align: left;
        }

        .dark-mode .livewire-supplier-summary .table-auto th,
        .dark-mode .livewire-supplier-summary .table-auto td {
            background-color: #333;
        }

        .livewire-supplier-summary .table-auto th {
            background-color: #D1D5DB;
            font-weight: bold;
        }

        body.dark-mode .nav-link {
            background-color: #333;
            color: lightgrey;
            border-color: #555;
        }

        body.dark-mode .nav-link:hover {
            background-color: #444;
        }

        body.dark-mode .nav-link.bg-blue-500 {
            background-color: #7c3aed;
            color: white;
            border-color: #7c3aed;
        }

        body.dark-mode .nav-link.bg-blue-500:hover {
            background-color: #7c3aed;
        }


        body.dark-mode .livewire-supplier-summary {
            background-color: #282828;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        body.dark-mode .livewire-supplier-summary h2,
        body.dark-mode .livewire-supplier-summary h3 {
            color: lightgrey;
        }


        body.dark-mode .livewire-supplier-summary .table-auto .border,
        body.dark-mode .livewire-supplier-summary .table-auto td {
            color: lightgrey;
            border: none;

        }

        body.dark-mode .livewire-supplier-summary .table-auto th {
            background-color: #333;
        }

        body.dark-mode .livewire-supplier-summary .status-badge.approved {
            background-color: #22c55e;
            color: white;
        }

        body.dark-mode .livewire-supplier-summary .status-badge.cancelled {
            background-color: lightcoral;
            color: white;
        }

        body.dark-mode .livewire-supplier-summary .status-badge.settled {
            color: white;
            background-color: dimgrey
        }


        ::-webkit-scrollbar {
            width: 12px;
            height: 8px;
        }

        ::-webkit-scrollbar-track { /* SCROLL RAIL */
            box-shadow: inset 0 0 0 rgba(0, 0, 0, 0.0);
            border-radius: 8px;
        }

        ::-webkit-scrollbar-thumb { /* SCROLLER */
            background: #6366f1;
            border-radius: 8px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }

        .dark-mode::-webkit-scrollbar-track { /* DARK */
            background: #09090B;
        }

        .dark-mode::-webkit-scrollbar-thumb { /* DARK MODE */
            background: #7c3aed;
        }


    </style>
@endif
