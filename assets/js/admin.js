/**
 * =========================================================
 * SSRINI HANDICRAFTS
 * ADMIN PANEL JAVASCRIPT
 * =========================================================
 */

document.addEventListener(
    'DOMContentLoaded',
    () => {


        /* =================================================
           ELEMENTS
           ================================================= */

        const sidebar =
            document.getElementById(
                'adminSidebar'
            );


        const mobileMenuButton =
            document.getElementById(
                'mobileMenuButton'
            );


        const refreshButton =
            document.getElementById(
                'refreshButton'
            );


        const dashboardRefreshButton =
            document.getElementById(
                'dashboardRefreshButton'
            );


        /* =================================================
           MOBILE SIDEBAR
           ================================================= */

        if (
            mobileMenuButton &&
            sidebar
        ) {

            mobileMenuButton.addEventListener(
                'click',
                () => {

                    sidebar.classList.toggle(
                        'open'
                    );

                }
            );

        }


        /* =================================================
           HEADER REFRESH
           ================================================= */

        if (refreshButton) {

            refreshButton.addEventListener(
                'click',
                () => {

                    refreshButton.disabled =
                        true;

                    refreshButton.style.opacity =
                        '0.6';


                    window.location.reload();

                }
            );

        }


        /* =================================================
           DASHBOARD REFRESH
           ================================================= */

        if (dashboardRefreshButton) {

            dashboardRefreshButton.addEventListener(
                'click',
                () => {

                    dashboardRefreshButton.disabled =
                        true;

                    dashboardRefreshButton.innerHTML =
                        '⏳ Refreshing...';


                    setTimeout(
                        () => {

                            window.location.reload();

                        },
                        300
                    );

                }
            );

        }


        /* =================================================
           SIDEBAR DROPDOWN
           ================================================= */

        const dropdowns =
            document.querySelectorAll(
                '.nav-dropdown'
            );


        dropdowns.forEach(
            (dropdown) => {

                dropdown.addEventListener(
                    'click',
                    () => {

                        const menuName =
                            dropdown.dataset.menu;


                        const submenu =
                            document.querySelector(
                                `[data-submenu="${menuName}"]`
                            );


                        if (!submenu) {
                            return;
                        }


                        submenu.classList.toggle(
                            'open'
                        );


                        const arrow =
                            dropdown.querySelector(
                                '.nav-arrow'
                            );


                        if (
                            submenu.classList.contains(
                                'open'
                            )
                        ) {

                            arrow.style.transform =
                                'rotate(90deg)';

                        } else {

                            arrow.style.transform =
                                'rotate(0deg)';

                        }

                    }
                );

            }
        );


        /* =================================================
           CLOSE MOBILE SIDEBAR
           ================================================= */

        const sidebarLinks =
            document.querySelectorAll(
                '.sidebar a'
            );


        sidebarLinks.forEach(
            (link) => {

                link.addEventListener(
                    'click',
                    () => {

                        if (
                            window.innerWidth <=
                            768
                        ) {

                            sidebar.classList.remove(
                                'open'
                            );

                        }

                    }
                );

            }
        );


        /* =================================================
           GLOBAL MODAL
           ================================================= */

        const modal =
            document.getElementById(
                'globalModal'
            );


        const modalClose =
            document.getElementById(
                'modalClose'
            );


        const modalTitle =
            document.getElementById(
                'modalTitle'
            );


        const modalBody =
            document.getElementById(
                'modalBody'
            );


        const modalFooter =
            document.getElementById(
                'modalFooter'
            );


        function openModal(
            title,
            body,
            footer = ''
        ) {

            if (!modal) {
                return;
            }


            modalTitle.textContent =
                title;


            modalBody.innerHTML =
                body;


            modalFooter.innerHTML =
                footer;


            modal.classList.add(
                'show'
            );


            modal.setAttribute(
                'aria-hidden',
                'false'
            );


            document.body.style.overflow =
                'hidden';

        }


        function closeModal() {

            if (!modal) {
                return;
            }


            modal.classList.remove(
                'show'
            );


            modal.setAttribute(
                'aria-hidden',
                'true'
            );


            document.body.style.overflow =
                '';

        }


        /* =================================================
           CLOSE MODAL
           ================================================= */

        if (modalClose) {

            modalClose.addEventListener(
                'click',
                closeModal
            );

        }


        if (modal) {

            modal.addEventListener(
                'click',
                (event) => {

                    if (
                        event.target ===
                        modal
                    ) {

                        closeModal();

                    }

                }
            );

        }


        document.addEventListener(
            'keydown',
            (event) => {

                if (
                    event.key ===
                    'Escape'
                ) {

                    closeModal();

                }

            }
        );


        /* =================================================
           EXPOSE MODAL
           ================================================= */

        window.adminModal = {

            open:
                openModal,

            close:
                closeModal

        };


        /* =================================================
           QUICK ACTIONS
           ================================================= */

        const quickActions =
            document.querySelectorAll(
                '[data-action]'
            );


        quickActions.forEach(
            (button) => {

                button.addEventListener(
                    'click',
                    () => {

                        const action =
                            button.dataset.action;


                        /* =========================
                           ADD PRODUCT
                           ========================= */

                        if (
                            action ===
                            'add-product'
                        ) {

                            openModal(

                                'Add Product',

                                `
                                <div class="form-group">

                                    <label
                                        class="form-label"
                                    >
                                        Product Name
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        placeholder="Enter product name"
                                    >

                                </div>


                                <div class="form-group">

                                    <label
                                        class="form-label"
                                    >
                                        SKU / Product Code
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        placeholder="Enter SKU"
                                    >

                                </div>


                                <div class="form-group">

                                    <label
                                        class="form-label"
                                    >
                                        Price
                                    </label>

                                    <input
                                        type="number"
                                        class="form-control"
                                        placeholder="Enter price"
                                    >

                                </div>
                                `,

                                `
                                <button
                                    type="button"
                                    class="btn btn-secondary"
                                    data-modal-close
                                >
                                    Cancel
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-primary"
                                >
                                    Add Product
                                </button>
                                `

                            );

                        }


                        /* =========================
                           NEW ORDER
                           ========================= */

                        if (
                            action ===
                            'new-order'
                        ) {

                            openModal(

                                'New Order',

                                `
                                <div class="empty-state">

                                    <div
                                        class="empty-state-icon"
                                    >
                                        📦
                                    </div>

                                    <h3>
                                        Order Management
                                    </h3>

                                    <p>
                                        New order creation
                                        will be connected
                                        to the order system.
                                    </p>

                                </div>
                                `,

                                `
                                <button
                                    type="button"
                                    class="btn btn-secondary"
                                    data-modal-close
                                >
                                    Close
                                </button>
                                `

                            );

                        }


                        /* =========================
                           CREATE INVOICE
                           ========================= */

                        if (
                            action ===
                            'create-invoice'
                        ) {

                            openModal(

                                'Create Invoice',

                                `
                                <div class="empty-state">

                                    <div
                                        class="empty-state-icon"
                                    >
                                        🧾
                                    </div>

                                    <h3>
                                        Invoice Management
                                    </h3>

                                    <p>
                                        Invoice creation
                                        will be connected
                                        to orders and customers.
                                    </p>

                                </div>
                                `,

                                `
                                <button
                                    type="button"
                                    class="btn btn-secondary"
                                    data-modal-close
                                >
                                    Close
                                </button>
                                `

                            );

                        }


                        /* =========================
                           ADD CATEGORY
                           ========================= */

                        if (
                            action ===
                            'add-category'
                        ) {

                            openModal(

                                'Add Category',

                                `
                                <div class="form-group">

                                    <label
                                        class="form-label"
                                    >
                                        Category Name
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        placeholder="e.g. Handbags"
                                    >

                                </div>


                                <div class="form-group">

                                    <label
                                        class="form-label"
                                    >
                                        Description
                                    </label>

                                    <textarea
                                        class="form-control"
                                        placeholder="Category description"
                                    ></textarea>

                                </div>
                                `,

                                `
                                <button
                                    type="button"
                                    class="btn btn-secondary"
                                    data-modal-close
                                >
                                    Cancel
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-primary"
                                >
                                    Add Category
                                </button>
                                `

                            );

                        }

                    }
                );

            }
        );


        /* =================================================
           DYNAMIC MODAL CLOSE BUTTONS
           ================================================= */

        document.addEventListener(
            'click',
            (event) => {

                if (
                    event.target.matches(
                        '[data-modal-close]'
                    )
                ) {

                    closeModal();

                }

            }
        );

    }
);