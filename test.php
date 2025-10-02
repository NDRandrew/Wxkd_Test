<script>
// Auto-load data 2 seconds after page is ready
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        // Trigger initial data load via AJAX
        const searchBtn = document.getElementById('searchBtn');
        if (searchBtn) {
            searchBtn.click();
        }
    }, 2000); // 2 second delay
});
</script>

-----

<tbody id="tableBody">
    <tr>
        <td colspan="16" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando dados...</span>
            </div>
            <p class="mt-3 text-muted">Carregando registros...</p>
        </td>
    </tr>
</tbody>