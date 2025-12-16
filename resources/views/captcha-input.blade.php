<div class="aron-captcha-field"
     style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">


    <img src="{{ app('aron-captcha')->generateBase64Image() }}"
         alt="captcha Code"
         id="aron-captcha-image"
         style="cursor: pointer; border: 1px solid #ccc; border-radius: 4px; height: 40px;">


    <button type="button"
            class="btn btn-sm btn-light"
            id="aron-refresh-captcha"
            title="Refresh CAPTCHA"
            style="font-size: 1.2em; border: 1px solid #ccc; padding: 5px 10px; cursor: pointer; border-radius: 4px; line-height: 1;">
        &#x21BB;
    </button>

    <input type="text"
           name="captcha"
           id="aron-captcha-input"
           placeholder="Please Enter Code"
           required
           autocomplete="off"
           style="height: 40px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; flex-grow: 1;">
    @error('captcha')
    <p class="text-sm text-red-600 mt-1" dir="rtl">{{ $message }}</p>
    @enderror
</div>

@section('botscript')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const refreshButton = document.getElementById('aron-refresh-captcha');
            const captchaImage = document.getElementById('aron-captcha-image');
            const captchaInput = document.getElementById('aron-captcha-input');

            if (refreshButton) {
                refreshButton.addEventListener('click', function(e) {
                    e.preventDefault();


                    const refreshUrl = '{{ route('aron-captcha.refresh') }}';

                    fetch(refreshUrl, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.image && captchaImage) {
                                captchaImage.src = data.image;
                                if (captchaInput) {
                                    captchaInput.value = '';
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error refreshing Aron CAPTCHA:', error);

                            alert('Error reloading security code.');
                        });
                });
            }


            if (captchaImage) {
                captchaImage.style.cursor = 'pointer';
                captchaImage.addEventListener('click', function() {
                    refreshButton.click();
                });
            }
        });
    </script>
@endsection
