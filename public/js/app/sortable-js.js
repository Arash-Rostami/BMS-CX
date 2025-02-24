class TableSorter {

    static getSortableJs() {
        if (typeof Sortable === 'undefined') {
            const script = document.createElement('script');
            script.src = "https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js";
            script.onload = TableSorter.setupSortable;
            document.head.appendChild(script);
        } else {
            TableSorter.setupSortable();
        }
    }

    static setupSortable() {
        let tableHeaderRow = document.querySelector('.fi-ta-table thead tr');
        let tableBody = document.querySelector('.fi-ta-table tbody');

        if (!tableHeaderRow || !tableBody) return;
        TableSorter.applyStoredOrder(tableHeaderRow, tableBody);
        TableSorter.initializeSortableCols(tableHeaderRow, tableBody);
        TableSorter.initializeEventListeners();
    }

    static getStorageKey() {
        try {
            const url = new URL(window.location.href);
            const pathParts = url.pathname.split('/').filter(part => part !== "");
            const searchParams = new URLSearchParams(url.search);

            let keyParts = pathParts;

            searchParams.forEach((value, key) => {
                keyParts.push(key);
                keyParts.push(value);
            });

            return `cached_${keyParts.join('_')}`;
        } catch (error) {
            console.error("Error creating storage key:", error);
            return "cached_invalid_url";
        }
    }

    static clearStoredOrder() {
        const key = TableSorter.getStorageKey();
        localStorage.removeItem(key);
        console.log(`Cleared stored order for key: ${key}`);
    }

    static initializeSortableCols(tableHeaderRow, tableBody) {
        if (tableHeaderRow && tableBody) {
            new Sortable(tableHeaderRow, {
                items: 'th:not(.fi-ta-selection-cell, .fi-ta-actions-header-cell)',
                handle: 'button',
                animation: 300,
                swapThreshold: 1,
                swap: true,
                swapClass: 'highlight',
                store: {
                    get: function (sortable) {
                        const key = TableSorter.getStorageKey();
                        try {
                            const storedData = JSON.parse(localStorage.getItem(key));
                            if (storedData && storedData.expires > new Date().getTime()) {
                                return storedData.data;
                            } else {
                                localStorage.removeItem(key);
                                return [];
                            }
                        } catch (error) {
                            console.error('Error retrieving sort order:', error);
                            localStorage.removeItem(key);
                            return [];
                        }
                    },
                    set: function (sortable) {
                        const key = TableSorter.getStorageKey();
                        const items = sortable.el.querySelectorAll('th');
                        const sortedOrder = Array.from(items).map(th => {
                            return Array.from(th.classList).find(cls => cls.startsWith('fi-table-header-cell-'));
                        });

                        try {
                            const now = new Date();
                            const expiration = now.getTime() + (30 * 24 * 60 * 60 * 1000);
                            localStorage.setItem(key, JSON.stringify({data: sortedOrder, expires: expiration}));

                        } catch (error) {
                            console.error('Error storing sort order:', error);
                        }
                    }
                },
                onUpdate: function (evt) {
                    TableSorter.reorderColumns(evt.oldIndex, evt.newIndex, tableBody);
                    TableSorter.updateColumnClasses(tableHeaderRow, tableBody);
                }
            });
        }
    }

    static reorderColumns(oldIndex, newIndex, tableBody) {
        Array.from(tableBody.rows).forEach(row => {
            const cellToMove = row.cells[oldIndex];
            if (cellToMove) {
                row.removeChild(cellToMove);
                const referenceCell = row.cells[newIndex];
                if (referenceCell) {
                    row.insertBefore(cellToMove, referenceCell);
                } else {
                    row.appendChild(cellToMove);
                }
            }
        });
    }

    static updateColumnClasses(tableHeaderRow, tableBody) {
        const headerCells = Array.from(tableHeaderRow.querySelectorAll('th'));
        Array.from(tableBody.rows).forEach(row => {
            headerCells.forEach((headerCell, index) => {
                if (row.cells[index]) {
                    row.cells[index].className = headerCell.className;
                }
            });
        });
    }

    static applyStoredOrder(tableHeaderRow, tableBody) {
        const key = TableSorter.getStorageKey();
        try {
            const storedData = JSON.parse(localStorage.getItem(key));
            if (storedData && storedData.expires > new Date().getTime()) {
                const storedOrder = storedData.data;

                if (tableHeaderRow && tableBody && storedOrder?.length) {
                    const headerCells = Array.from(tableHeaderRow.querySelectorAll('th'));

                    // Reorder header
                    storedOrder.forEach((className, newIndex) => {
                        if (!className) return;
                        const oldIndex = headerCells.findIndex(h => h.classList.contains(className));
                        if (oldIndex === -1 || oldIndex === newIndex) return;
                        const cellToMove = headerCells[oldIndex];
                        tableHeaderRow.removeChild(cellToMove);
                        const referenceCell = tableHeaderRow.querySelectorAll('th')[newIndex];
                        referenceCell
                            ? tableHeaderRow.insertBefore(cellToMove, referenceCell)
                            : tableHeaderRow.appendChild(cellToMove);
                        headerCells.splice(oldIndex, 1);
                        headerCells.splice(newIndex, 0, cellToMove);
                    });

                    // Reorder body
                    Array.from(tableBody.rows).forEach(row => {
                        storedOrder.forEach((className, newIndex) => {
                            if (!className) return;
                            const cells = Array.from(row.cells);
                            const oldIndex = cells.findIndex(td => td.classList.contains(className.replace('header-cell', 'cell')));
                            if (oldIndex === -1 || oldIndex === newIndex) return;
                            const cellToMove = row.cells[oldIndex];
                            row.removeChild(cellToMove);
                            const referenceCell = row.cells[newIndex];
                            referenceCell
                                ? row.insertBefore(cellToMove, referenceCell)
                                : row.appendChild(cellToMove);
                        });
                    });
                }
            } else {
                localStorage.removeItem(key);
            }
        } catch (error) {
            console.error('Error retrieving or applying stored order:', error);
            localStorage.removeItem(key);
        }
    }

    static initializeEventListeners() {
        window.addEventListener('refreshSortJs', TableSorter.setupSortable);
        window.addEventListener('clearTableSort', TableSorter.clearStoredOrder);
    }
}


window.addEventListener('DOMContentLoaded', () => {
    TableSorter.getSortableJs();
});


// Setup after URL changes
['replaceState'].forEach(event => {
    const originalMethod = history[event];
    history[event] = function () {
        window.dispatchEvent(new Event('urlChange'));
        return originalMethod.apply(this, arguments);
    };
});
window.addEventListener('urlChange', () => {
    setTimeout(TableSorter.getSortableJs, 2000);
});
