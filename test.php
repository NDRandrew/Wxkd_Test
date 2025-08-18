setTimeout(() => {
    console.log('Reloading after Restore...');

    CheckboxModule.clearSelections();

    const currentFilter = FilterModule.currentFilter;
    FilterModule.loadTableData(currentFilter);

}, 1000);

setTimeout(() => {
    this.updateConfirmedCount();
}, 1500);


---------------------------


setTimeout(function() {
    console.log('Reloading after Restore...');

    CheckboxModule.clearSelections();

    var currentFilter = FilterModule.currentFilter;
    FilterModule.loadTableData(currentFilter);

}, 1000);

setTimeout(function() {
    updateConfirmedCount();
}, 1500);
