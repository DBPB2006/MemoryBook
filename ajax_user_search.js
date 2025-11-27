function setupUserSearch(inputId, suggestionsId, excludeSelf = false) {
    const input = document.getElementById(inputId);
    const suggestions = document.getElementById(suggestionsId);

    if (!input || !suggestions) {
        return;
    }

    input.addEventListener('input', function() {
        const prefix = input.value.trim();
        suggestions.innerHTML = '';

        if (prefix.length < 1) {
            return;
        }

        const formData = new URLSearchParams();
        formData.append('prefix', prefix);
        if (excludeSelf) {
            formData.append('exclude_self', '1');
        }

        fetch('user_search_suggest.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            suggestions.innerHTML = '';
            if (data.length > 0) {
                data.forEach(user => {
                    const div = document.createElement('div');
                    div.textContent = `${user.name} (${user.email})`;
                    div.className = 'px-4 py-2 rounded bg-[#E8E3F5] text-[#8B7EC8] cursor-pointer hover:bg-[#D1C7EB]';
                    
                    div.addEventListener('click', () => {
                        input.value = user.name;
                        suggestions.innerHTML = '';
                        
                        input.dispatchEvent(new CustomEvent('user-selected', {
                            detail: { name: user.name, email: user.email }
                        }));
                    });
                    suggestions.appendChild(div);
                });
            } else {
                suggestions.innerHTML = '<div class="p-2 text-gray-500">No users found</div>';
            }
        });
    });

    input.addEventListener('blur', () => {
        setTimeout(() => {
            suggestions.innerHTML = '';
        }, 200);
    });
}
