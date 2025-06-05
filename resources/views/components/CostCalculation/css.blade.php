@if(request()->path() === 'cost-calculation')
    <style>
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
            padding: 30px;
            margin: 50px auto;
            width: 98%;
            color: #1f2937;
        }

        .nav-tabs {
            margin-bottom: 1rem;
            margin-top: 1.5rem;
            border-bottom: 1px solid #ccc;
        }


        .nav-link {
            background-color: #e5e7eb;
            border: 1px solid #d3d3d3;
            border-bottom: none;
            border-radius: 0.5rem 0.5rem 0 0;
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
            border-color: #6366f1;
            z-index: 1;
            position: relative;
        }

        .btn.bg-blue-500 {
            background-color: #6366f1;
            color: white;
            z-index: 1;
        }

        .nav-link.bg-blue-500:hover {
            background-color: #6366f1;
        }


        .insight {
            color: #6366f1 !important;
        }

        body.dark-mode .insight {
            color: #7c3aed !important;
        }

        .insight {
            color: #6366f1 !important;
        }

        .table-placeholder {
            border: 2px dashed #D1D5DB;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            color: #6B7280;
            min-height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-grid {
            display: grid;
            gap: 0.25rem;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            padding: 0;
        }

        .form-grid label {
            display: block;
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
        }


        .form-grid > div:not(.submit-button-container), .alternative {
            padding: 0.4rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            background-color: #D1D5DB;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            display: flex;
            flex-direction: column;
        }

        body.dark-mode .form-grid > div:not(.submit-button-container) {
            border-color: #444;
            background-color: #333;
            color: lightgrey;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3), 0 1px 2px 0 rgba(0, 0, 0, 0.2);
        }

        body.dark-mode .alternative {
            background-color: #333333 !important;
            border: none;
        }


        .form-span-2 {
            grid-column: span 2;
        }

        @media (min-width: 768px) {
            .form-span-2 {
                grid-column: span 2;
            }
        }

        .form-input,
        .form-textarea {
            display: block;
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            color: #333333;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            flex-grow: 1;
        }

        .form-input::placeholder,
        .form-textarea::placeholder {
            color: #a0aec0;
            opacity: 1;
        }

        .form-input:focus,
        .form-textarea:focus {
            border-color: #6366f1;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.25);
        }

        body.dark-mode .form-input,
        body.dark-mode .form-textarea {
            background-color: #282828;
            color: lightgrey;
            border-color: #555;
        }

        body.dark-mode .form-input::placeholder,
        body.dark-mode .form-textarea::placeholder {
            color: #718096;
        }

        body.dark-mode .form-input:focus,
        body.dark-mode .form-textarea:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 0.25rem rgba(124, 58, 237, 0.25);
        }

        .form-textarea {
            resize: vertical;
        }

        .error-message {
            display: block;
            margin-top: 0.25rem;
            font-size: 0.875rem;
            color: #e53e3e;
        }

        .error-box {
            background-color: #fed7d7;
            color: #9b2c2c;
            border: 1px solid #feb2b2;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        body.dark-mode .error-box {
            background-color: #440000;
            color: #ffcccc;
            border-color: #770000;
        }

        .success-message {
            background-color: #c6f6d5;
            color: #2f855a;
            border: 1px solid #9ae6b4;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        body.dark-mode .success-message {
            background-color: #004400;
            color: #ccffcc;
            border-color: #007700;
        }

        .submit-button, .main-color-button {
            display: inline-block;
            background-color: #6366f1;
            color: white;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            transition: background-color 0.2s ease-in-out;
            cursor: pointer;
            border: none;
            margin: auto auto auto auto;
        }

        .submit-button:hover, .main-color-button:hover {
            background-color: #4f46e5;
        }


        body.dark-mode .submit-button, body.dark-mode .main-color-button {
            background-color: #7c3aed;
        }

        body.dark-mode .submit-button:hover, body.dark-mode .main-color-button:hover {
            background-color: #6d28d9;
        }

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

        .my-dark-class:hover {
            background-color: #d1d5db;
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
            color: lightgrey;
        }


        body.dark-mode .nav-link {
            background-color: #333;
            border-color: #555;
            color: lightgrey;
        }

        body.dark-mode .btn.bg-blue-500 {
            background-color: #7c3aed;
            color: #f8fafc;
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

        body.dark-mode .table-placeholder {
            border-color: #555;
            color: #999;
        }

        body.dark-mode h2,
        body.dark-mode h3,
        body.dark-mode h4,
        body.dark-mode h5,
        body.dark-mode h6 {
            color: lightgrey;
        }

        ::-webkit-scrollbar {
            height: 8px;
            width: 12px;
        }

        ::-webkit-scrollbar-track {
            box-shadow: inset 0 0 0 rgba(0, 0, 0, 0.0);
            border-radius: 8px;
        }

        ::-webkit-scrollbar-thumb {
            background: #6366f1;
            background-clip: padding-box;
            border: 2px solid transparent;
            border-radius: 8px;
        }

        body.dark-mode::-webkit-scrollbar-track {
            background: #09090B;
        }

        body.dark-mode::-webkit-scrollbar-thumb {
            background: #7c3aed;
        }

        body.dark-mode::-webkit-scrollbar-thumb:hover {
            background-color: #6d28d9;
        }

        @keyframes spin {
            0% {
                transform: translate(-50%, -50%) rotate(0deg);
            }
            100% {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        @media (min-width: 768px) {
            .form-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            }

            .form-span-2 {
                grid-column: span 2;
            }
        }

        @media (min-width: 1024px) {
            .form-grid {
                grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            }

            .form-span-2 {
                grid-column: span 2;
            }
        }

        .table-auto {
            border-collapse: separate;
            border-radius: 1rem;
            border-spacing: 3px;
            overflow: hidden;
            padding-bottom: 30px;
            width: 100%;
        }

        .table-auto th,
        .table-auto td {
            background-color: #D1D5DB;
            text-align: left;
            border: none;
        }

        .table-auto th {
            font-weight: bold;
        }

        .dark-mode .table-auto th,
        .dark-mode .table-auto td {
            background-color: #333;
            border: none;
        }

        .table-pagination {
            padding-left: 1rem;
            padding-right: 1rem;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            border-top: 1px solid #E5E7EB;
        }

        .dark-mode .table-pagination {
            border-top-color: #374151;
        }

        .btn-view {
            color: #4F46E5;
            margin-right: 0.75rem;
            transition: color 0.2s ease;
        }

        .btn-view:hover {
            color: #312E81;
        }

        .dark-mode .btn-view {
            color: #7c3aed;
        }

        .dark-mode .btn-view:hover {
            color: #A5B4FC;
        }

        .btn-delete {
            color: #DC2626;
            transition: color 0.2s ease;
        }

        .btn-save {
            color: green;
            transition: color 0.2s ease;
        }

        .btn-delete:hover {
            color: #7F1D1D;
        }

        .dark-mode .btn-delete {
            color: #F87171;
        }

        .dark-mode .btn-delete:hover {
            color: #FCA5A5;
        }

        .badge {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
            display: inline-flex;
            font-size: 0.75rem;
            line-height: 1.25rem;
            font-weight: 600;
            border-radius: 9999px;
        }

        .badge-accepted {
            background-color: #D1FAE5;
            color: #065F46;
        }

        .dark-mode .badge-accepted {
            background-color: #064E3B;
            color: #BBF7D0;
        }

        .badge-rejected {
            background-color: #FEE2E2;
            color: #991B1B;
        }

        .dark-mode .badge-rejected {
            background-color: #7F1D1D;
            color: #FECACA;
        }

        .badge-sold {
            background-color: #DBEAFE;
            color: #1E3A8A;
        }

        .dark-mode .badge-sold {
            background-color: #1E40AF;
            color: #BFDBFE;
        }

        .badge-default {
            background-color: #F3F4F6;
            color: #1F2937;
        }

        .dark-mode .badge-default {
            background-color: #374151;
            color: #D1D5DB;
        }


        .custom-paginator {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            padding: 1rem 0;
        }

        .custom-paginator .page-btn {
            background-color: #D1D5DB;
            color: #1F2937;
            border: 1px solid #9CA3AF;
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 0.375rem;
            transition: background 0.2s, color 0.2s;
            cursor: pointer;
        }

        /* hover */
        .custom-paginator .page-btn:hover:not([disabled]):not(.active) {
            background-color: #E5E7EB;
            color: #111827;
        }

        /* active page */
        .custom-paginator .page-btn.active,
        .custom-paginator .page-btn[aria-current="page"] {
            background-color: #6366f1;
            color: white;
            font-weight: 700;
            cursor: default;
        }

        .custom-paginator .page-btn:disabled {
            background-color: #E5E7EB;
            color: #6B7280;
            cursor: not-allowed;
        }

        /* dark mode */
        .dark-mode .custom-paginator .page-btn {
            background-color: #333;
            color: #D1D5DB;
            border-color: #4B5563;
        }

        .dark-mode .custom-paginator .page-btn:hover:not([disabled]):not(.active) {
            background-color: #4B5563;
            color: #F9FAFB;
        }

        .dark-mode .custom-paginator .page-btn.active,
        .dark-mode .custom-paginator .page-btn[aria-current="page"] {
            background-color: #7c3aed;
            color: #F3F4F6;
        }

        .dark-mode .custom-paginator .page-btn:disabled {
            background-color: transparent;
            color: #9CA3AF;
        }


        .label-text {
            color: #4B5563;
        }
        body.dark-mode .label-text {
            color: #9CA3AF;
        }

        .note-box {
            background-color: #F9FAFB;
            padding: 0.75rem;
            border-radius: 0.5rem;
        }
        .dark-mode .note-box {
            background-color: #333333 !important;
        }

        #toast-notification {
            position: fixed !important;
            top: 1.25rem !important;
            right: 1.25rem !important;
            left: auto !important;
        }


    </style>
@endif
