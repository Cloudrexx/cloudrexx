var MultiSelectElementScopes = cx.variables.get('multi-select_element_scopes', 'multi-select');
var MultiSelectElements = [];

const MultiSelectElement = function(scope) {
    this.delimiter = cx.variables.get('delimiter', scope);
    this.wrapper = document.getElementById(cx.variables.get('associated_wrapper', scope));
    this.form = document.getElementById(cx.variables.get('associated_form', scope));
    this.associated = this.wrapper.querySelector('.'+cx.variables.get('associated_select', scope));
    this.notAssociated = this.wrapper.querySelector('.'+cx.variables.get('not_associated_select', scope));
    this.buttonWrapper = this.wrapper.querySelector('.control-buttons');
    this.addBtn = this.wrapper.querySelector('.addBtn');
    this.removeBtn =  this.wrapper.querySelector('.removeBtn');
    this.associatedSelectAllLinks = this.wrapper.querySelector('.multi-select-associated .select-all');
    this.notAssociatedSelectAllLinks = this.wrapper.querySelector('.multi-select-not-associated .select-all');
    this.associatedDeselectAllLinks = this.wrapper.querySelector('.multi-select-associated .deselect-all');
    this.notAssociatedDeselectAllLinks = this.wrapper.querySelector('.multi-select-not-associated .deselect-all');

    this.moveOptions = function (from, dest, add, remove) {
        if (from.selectedIndex < 0) {
            if (from.options[0] != null)
                from.options[0].selected = true;
            from.focus();
            return false;
        } else {
            for (var i = 0; i<from.length; ++i) {
                if (from.options[i].selected) {
                    dest.options[dest.length] = new Option(from.options[i].text, from.options[i].value, false, false);
                }
            }
            for (var i = from.length-1; i>=0; --i) {
                if (from.options[i].selected) {
                    from.options[i] = null;
                }
            }
        }
        flagMasterChanged = 1;
        // Enable or disable the buttons
        if (from.options.length > 0) {
            add.disabled = 0;
        } else {
            add.disabled = 1;
        }
        if (dest.options.length > 0) {
            remove.disabled = 0;
        } else {
            remove.disabled = 1;
        }

        this.size();
    };

    this.selectOptions = function(element, on_or_off) {
        if (element) {
            for (var i = 0; i < element.length; ++i) {
                element.options[i].selected = on_or_off;
            }
        }
        this.size();
    };

    this.sliceOptions = function(element) {
        if (element.hasChildNodes()) {
            element.querySelectorAll('option').forEach((option) => {
                if (option.offsetWidth > this.wrapper.offsetWidth) {
                    let firstElement = '';
                    let dots = '';
                    let previousElement = '';
                    let lastElement = '';

                    const pieces = option.textContent.split(this.delimiter);

                    if (pieces[0] != null && pieces[0].length > 30) {
                        firstElement = pieces[0].slice(0, 30) + '...';
                    } else {
                        firstElement = pieces[0];
                    }
                    if (pieces != null && pieces.length > 3) {
                        dots = this.delimiter + ' ... ';
                    }
                    if (pieces.length >= 3 && pieces[pieces.length - 2] != null) {
                        if (pieces[pieces.length - 2].length > 30) {
                            previousElement = this.delimiter + pieces[pieces.length - 2].slice(0, 30) + '...';
                        } else {
                            previousElement = this.delimiter + pieces[pieces.length - 2];
                        }
                    }
                    if (pieces.length >= 2 && pieces[pieces.length - 1] != null) {
                        if (pieces[pieces.length - 1].length > 60) {
                            lastElement = this.delimiter + pieces[pieces.length - 1].slice(0, 60) + '...';
                        } else {
                            lastElement = this.delimiter + pieces[pieces.length - 1];
                        }
                    }

                    option.textContent = firstElement + dots + previousElement + lastElement;
                }
            });
        }
    };

    this.calculateWidth = function() {
        let widthOfAllElements = this.associated.offsetWidth + this.buttonWrapper.offsetWidth
            + this.notAssociated.offsetWidth;

        return this.wrapper.offsetWidth < widthOfAllElements;
    };

    this.size = function() {
        if (this.calculateWidth()) {
            this.wrapper.classList.remove('wide');
            this.sliceOptions(this.associated);
            this.sliceOptions(this.notAssociated);
        }

        if (this.calculateWidth()) {
            this.wrapper.classList.add('wide');
        } else {
            this.wrapper.classList.remove('wide');
        }
    };

    this.initEventListeners = function () {
        this.form.addEventListener('submit', () => {
            this.selectOptions(this.associated, true);
        });

        this.addBtn.addEventListener('click', (e) => {
            e.preventDefault();
            this.moveOptions(
                this.notAssociated,
                this.associated,
                this.addBtn,
                this.removeBtn
            );
        });
        this.removeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            this.moveOptions(
                this.associated,
                this.notAssociated,
                this.removeBtn,
                this.addBtn
            );
        });
        this.associatedSelectAllLinks.addEventListener('click', (e) => {
            e.preventDefault();
            this.selectOptions(this.associated, true);
        });
        this.notAssociatedSelectAllLinks.addEventListener('click', (e) => {
            e.preventDefault();
            this.selectOptions(this.notAssociated, true);
        });
        this.associatedDeselectAllLinks.addEventListener('click', (e) => {
            e.preventDefault();
            this.selectOptions(this.associated, false);
        });
        this.notAssociatedDeselectAllLinks.addEventListener('click', (e) => {
            e.preventDefault();
            this.selectOptions(this.notAssociated, false);
        });
    };

    this.initEventListeners();
};

function initMultiSelectElement() {
    MultiSelectElementScopes.forEach(function (scope) {
        const multiSelect = new MultiSelectElement(scope);
        MultiSelectElements.push(multiSelect);
        window.addEventListener('resize', function () {
            multiSelect.size();
        });
        multiSelect.size();
    });
}

function resizeMultiSelectElements() {
    MultiSelectElements.forEach(function (multiSelect) {
        multiSelect.size();
    });
}

document.addEventListener("DOMContentLoaded", function() {
    initMultiSelectElement();
});


