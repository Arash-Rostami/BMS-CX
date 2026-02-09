@if(request()->path() === 'case-summary')
    <style>
        /* Base & Layout */
        [x-cloak] {
            display: none !important;
        }

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

        /* Search */
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
            background-position: left bottom, right bottom, right top, left top;
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
            border-radius: 0.5rem;
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

        /* Navigation */
        .nav-tabs {
            margin-top: 1.5rem;
            margin-bottom: 1rem;
        }

        .nav-link {
            position: relative;
            overflow: hidden;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            color: #374151;
            background-color: #e5e7eb;
            border: 1px solid #d3d3d3;
            border-bottom: none;
            border-radius: 0.5rem 0.5rem 0 0;
            cursor: pointer;
            padding-bottom: 8px;
            transition: background-color 0.3s ease, color 0.3s ease;
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

        .nav-link:hover {
            background-color: #d1d5db;
        }

        .nav-link:hover::after {
            transform: scaleX(1);
        }

        .nav-link.bg-blue-500 {
            color: white;
            background-color: #6366f1;
        }

        .nav-link.bg-blue-500:hover {
            background-color: #6366f1;
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

        /* Status Badges */
        .status-badge {
            margin-left: 0.5rem;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: bold;
            border-radius: 0.375rem;
            letter-spacing: 0.05em;
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

        /* Buttons */
        .u-btn,
        button.u-btn,
        input[type="button"].u-btn,
        input[type="submit"].u-btn,
        .my-dark-class,
        .details-toggle-btn,
        .pagination-button,
        .nav-link,
        .status-badge,
        button.bg-blue-500,
        button.bg-gray-200 {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 40px;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            line-height: 1;
            cursor: pointer;
            transition: background-color 0.18s ease, box-shadow 0.18s ease, transform 0.12s ease;
            border: 1px solid transparent;
            white-space: nowrap;
            vertical-align: middle;
        }

        .my-dark-class,
        button.bg-gray-200,
        .nav-link.bg-gray-200 {
            background-color: #e5e7eb;
            color: #374151;
            border-color: #d3d3d3;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
        }

        .my-dark-class:hover,
        button.bg-gray-200:hover,
        .nav-link.bg-gray-200:hover,
        .details-toggle-btn:hover,
        .pagination-button:hover {
            background-color: #d1d5db;
            transform: translateY(-1px);
        }

        button.bg-blue-500,
        .nav-link.bg-blue-500 {
            background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 6px 12px rgba(99, 102, 241, 0.12);
        }

        button.bg-blue-500:hover,
        .nav-link.bg-blue-500:hover {
            filter: brightness(0.98);
            transform: translateY(-1px);
        }

        button.bg-green-500,
        button.bg-green-500:hover {
            color: #ffffff;
            background: linear-gradient(90deg, #10b981, #059669);
            border-color: transparent;
            box-shadow: 0 6px 12px rgba(16, 185, 129, 0.08);
        }

        button.bg-green-500:hover {
            transform: translateY(-1px);
        }

        .u-btn--icon,
        button.u-btn--icon,
        .my-dark-class.u-btn--icon {
            min-width: 40px;
            padding: 0.5rem;
        }

        .u-btn:focus,
        button:focus,
        .my-dark-class:focus,
        .details-toggle-btn:focus,
        .nav-link:focus {
            outline: 2px solid rgba(99, 102, 241, 0.28);
            outline-offset: 2px;
        }

        .nav-link {
            padding: 0.5rem 1.25rem;
            border-radius: 0.5rem 0.5rem 0 0;
            border-bottom: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 40px;
        }

        .details-toggle-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            background-color: #e5e7eb;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .details-toggle-btn:hover {
            background-color: #d1d5db;
            border-color: #9ca3af;
        }

        .details-toggle-btn.btn-active {
            background-color: #6366f1;
            color: white;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
        }

        .details-toggle-btn.btn-active:hover {
            background-color: #4f46e5;
            border-color: #4f46e5;
        }

        .pagination-button {
            padding: 0.5rem 1rem;
            min-height: 36px;
            padding: 0.4rem 0.8rem;
            background-color: #e5e7eb;
            border-radius: 0.45rem;
            transition: background-color 0.2s;
        }

        .pagination-button:hover {
            background-color: #d1d5db;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.03em;
        }

        /* Pagination */
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

        /* Spinner & Loader */
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

        /* Livewire Components */
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

        /* Chat Widget */
        .cw-root {
            position: fixed;
            bottom: 1.5rem;
            left: 1rem;
            z-index: 9999;
            font-family: inherit;
            pointer-events: auto;
        }

        .cw-root--no-transform {
            transform: none;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }

        .cw-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 40px;
            padding: 0.45rem 0.75rem;
            border-radius: 0.5rem;
            font-weight: 500;
            line-height: 1;
            cursor: pointer;
            transition: background-color 0.18s ease, box-shadow 0.18s ease, transform 0.12s ease;
            border: 1px solid transparent;
            background-color: #e5e7eb;
            color: #374151;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
            vertical-align: middle;
            width: auto;
            height: auto;
        }

        .cw-toggle.u-btn--icon {
            min-width: 40px;
            padding: 0.5rem;
        }

        .cw-toggle[aria-pressed="true"],
        .cw-toggle.x-active,
        .cw-toggle[aria-expanded="true"] {
            background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 6px 12px rgba(99, 102, 241, 0.12);
        }

        .cw-toggle:hover {
            transform: translateY(-1px);
        }

        .cw-toggle:focus, .cw-btn-max:focus, .cw-btn-close:focus {
            outline: 2px solid rgba(99, 102, 241, 0.25);
            outline-offset: 2px;
        }

        .cw-material {
            font-size: 1.25rem;
            display: inline-block;
            line-height: 1;
        }

        .cw-icon--open .cw-material {
            font-size: 1.375rem;
        }

        .cw-icon--close .cw-material {
            font-size: 1.375rem;
            color: #6b7280;
        }

        .cw-panel {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            height: 85vh;
            max-height: 85vh;
            background: #ffffff;
            border-radius: 1rem 1rem 0 0;
            box-shadow: 0 25px 50px rgba(16, 24, 40, 0.08);
            overflow: hidden;
            transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            z-index: 10000;
        }

        .cw-panel--maximized {
            position: fixed !important;
            inset: 0 !important;
            width: 100% !important;
            height: 100% !important;
            max-width: none !important;
            max-height: none !important;
            border-radius: 0 !important;
            margin: 0 !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            top: 0 !important;
            z-index: 2147483640 !important;
        }

        .cw-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4);
            padding: 0.75rem 1rem;
            color: #fff;
            height: 52px;
            flex: 0 0 52px;
        }

        .cw-header-left {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .cw-header-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .cw-status-dot {
            width: 10px;
            height: 10px;
            border-radius: 9999px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: background-color 0.25s ease;
        }

        .cw-status-dot--ok {
            background: #34d399;
        }

        .cw-status-dot--maintenance {
            background: #ef4444;
        }

        .cw-title {
            font-size: 0.75rem;
            color: #fff;
            font-weight: 500;
        }

        .cw-btn-max, .cw-btn-close {
            background: transparent;
            border: none;
            color: #fff;
            padding: 0.25rem;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.18s ease;
            cursor: pointer;
        }

        .cw-btn-max:hover, .cw-btn-close:hover {
            background: rgba(255, 255, 255, 0.12);
        }

        .cw-iframe {
            width: 100%;
            height: calc(100% - 52px);
            border: 0;
            display: block;
            background: transparent;
            flex: 1 1 auto;
        }

        .cw-maintenance {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            height: calc(100% - 52px);
            text-align: center;
            color: #374151;
        }

        .cw-maint-icon {
            font-size: 3.5rem;
            color: #f59e0b;
            margin-bottom: 0.75rem;
        }

        .cw-maint-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #111827;
        }

        .cw-maint-sub {
            color: #4b5563;
            margin-bottom: 0.5rem;
        }

        .cw-maint-note {
            color: #6b7280;
            font-size: 0.875rem;
        }

        /* Utility Classes */
        .text-green-500 {
            color: #22c55e !important;
        }

        .text-gray-500 {
            color: dimgrey !important;
        }

        .insight {
            color: #6366f1 !important;
        }

        .main-color-complement {
            color: #6466F1;
        }

        .tooltip {
            padding: 6px;
            min-width: 110px;
            color: white;
            background-color: #6466F1;
            border-radius: 5px;
            text-align: center;
        }

        .help-cursor {
            cursor: help !important;
        }

        .cursor-not-allowed,
        .disabled {
            cursor: not-allowed !important;
            pointer-events: all !important;
        }

        .enabled {
            cursor: pointer;
        }

        .whitespace-pre {
            white-space: pre;
        }

        .animate-pulse {
            animation: cw-pulse 1.8s infinite;
        }

        .animate-spectrum {
            animation: color-spectrum 4s linear infinite;
        }

        /* Scrollbars */
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

        /* Animations */
        @keyframes draw-border {
            0% { background-size: 0 2px, 2px 0, 0 2px, 2px 0; }
            25% { background-size: 100% 2px, 2px 0, 0 2px, 2px 0; }
            50% { background-size: 100% 2px, 2px 100%, 0 2px, 2px 0; }
            75% { background-size: 100% 2px, 2px 100%, 100% 2px, 2px 0; }
            100% { background-size: 100% 2px, 2px 100%, 100% 2px, 2px 100%; }
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        @keyframes cw-pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.9; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes color-spectrum {
            0% { color: #60a5fa; }
            20% { color: #a78bfa; }
            40% { color: #f472b6; }
            60% { color: #fbbf24; }
            80% { color: #34d399; }
            100% { color: #60a5fa; }
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
            .cw-root {
                top: 75%;
                bottom: auto;
                transform: translateY(-50%);
            }

            .cw-panel:not(.cw-panel--maximized) {
                position: absolute;
                bottom: 4rem;
                left: 0;
                right: auto;
                width: 420px;
                height: 600px;
                max-height: 80vh;
                border-radius: 1rem;
            }

            .cw-header {
                padding: 0.5rem 1rem;
                height: 48px;
                flex: 0 0 48px;
            }

            .cw-iframe {
                height: calc(100% - 48px);
            }

            .cw-maintenance {
                height: calc(100% - 48px);
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

            .u-btn,
            .my-dark-class,
            .nav-link,
            .details-toggle-btn {
                min-height: 36px;
                padding: 0.45rem 0.75rem;
                font-size: 0.95rem;
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

        /* Dark Mode */
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

        body.dark-mode .loader-gradient {
            background: linear-gradient(90deg, #7c3aed, #a855f7, #06b6d4);
        }

        body.dark-mode .loader-gradient::after {
            background: #282828;
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

        body.dark-mode .my-dark-class,
        body.dark-mode button.bg-gray-200,
        body.dark-mode .nav-link.bg-gray-200 {
            color: lightgrey;
            background-color: #282828;
            border-color: #444;
            box-shadow: 0 2px 4px rgba(255, 255, 255, 0.1);
        }

        body.dark-mode .my-dark-class:hover {
            background-color: #333;
        }

        body.dark-mode button.bg-blue-500,
        body.dark-mode .nav-link.bg-blue-500 {
            background: linear-gradient(90deg, #7c3aed, #a855f7, #06b6d4);
            color: #fff;
            box-shadow: 0 6px 12px rgba(124, 58, 237, 0.08);
        }

        body.dark-mode button.bg-green-500 {
            background: linear-gradient(90deg, #10b981, #047857);
            color: #fff;
        }

        body.dark-mode .u-btn--icon,
        body.dark-mode .my-dark-class.u-btn--icon {
            background-color: #2b2b2b;
            border-color: #3b3b3b;
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

        body.dark-mode .details-toggle-btn {
            color: lightgrey;
            background-color: #333;
            border-color: #555;
        }

        body.dark-mode .details-toggle-btn:hover {
            background-color: #444;
            border-color: #666;
        }

        body.dark-mode .details-toggle-btn.btn-active {
            background-color: #7c3aed;
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.3);
        }

        body.dark-mode .details-toggle-btn.btn-active:hover {
            background-color: #6d28d9;
            border-color: #6d28d9;
        }

        body.dark-mode .balance-net.text-red-600 {
            color: #fca5a5 !important;
        }

        body.dark-mode .balance-net.text-blue-600 {
            color: #93c5fd !important;
        }

        body.dark-mode .balance-credit.text-green-500 {
            color: #4ade80 !important;
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

        body.dark-mode .cw-root {
            transform: translateY(-50%);
        }

        body.dark-mode .cw-toggle {
            background: #1f2937;
            color: #d1d5db;
            border-color: #3f3f46;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.6), 0 4px 6px -2px rgba(0, 0, 0, 0.4);
        }

        body.dark-mode .cw-toggle:hover {
            transform: translateY(-1px);
        }

        body.dark-mode .cw-panel {
            background: linear-gradient(145deg, #141414 0%, #1f1f1f 100%);
            border: 1px solid #2b2b2b;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.8);
        }

        body.dark-mode .cw-panel--maximized {
            border-radius: 0;
        }

        body.dark-mode .cw-header {
            background: linear-gradient(90deg, #7c3aed, #a855f7, #06b6d4);
            color: #e6e6e6;
        }

        body.dark-mode .cw-title {
            color: #d1d5db;
        }

        body.dark-mode .cw-status-dot--ok {
            background: #10b981;
        }

        body.dark-mode .cw-status-dot--maintenance {
            background: #ef4444;
        }

        body.dark-mode .cw-status-dot {
            border-color: rgba(255, 255, 255, 0.06);
            box-shadow: none;
        }

        body.dark-mode .cw-btn-max {
            color: #e6e6e6;
        }

        body.dark-mode .cw-btn-max:hover {
            background: rgba(255, 255, 255, 0.04);
        }

        body.dark-mode .cw-iframe {
            background: #0b0b0b;
        }

        body.dark-mode .cw-maintenance {
            color: #cfcfcf;
        }

        body.dark-mode .cw-maint-icon {
            color: #f59e0b;
        }

        body.dark-mode .cw-maint-title {
            color: #f3f4f6;
        }

        body.dark-mode .cw-maint-sub {
            color: #c7c7c7;
        }

        body.dark-mode .cw-maint-note {
            color: #9ca3af;
        }

        body.dark-mode .animate-spectrum {
            filter: saturate(0.9) brightness(0.95);
        }

        body.dark-mode .cw-panel::-webkit-scrollbar,
        body.dark-mode .cw-panel .overflow-x-auto::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        body.dark-mode .cw-panel::-webkit-scrollbar-track {
            background: transparent;
        }

        body.dark-mode .cw-panel::-webkit-scrollbar-thumb {
            background: #7c3aed;
            border-radius: 8px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }

        body.dark-mode .cw-panel::-webkit-scrollbar-thumb:hover {
            background: #6d28d9;
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
