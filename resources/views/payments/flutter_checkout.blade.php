<form>
    <script src="https://checkout.flutterwave.com/v3.js"></script>
    <button type="button" onClick="makePayment()" id="enviar"></button>
</form>

<script>
function makePayment() {
    FlutterwaveCheckout({
        public_key: "{{$data['public_key']}}",
        tx_ref: "{{$data['ref']}}",
        amount: "{{$data['total']}}",
        currency: "NGN",
        country: "NG",
        payment_options: "card, mobilemoneyghana, ussd",
        meta: {
            consumer_id: "{{$data['user_id']}}",
        },
        customer: {
            email: "{{$data['user_email']}}",
            name: "{{$data['user_name']}}",
        },
        callback: function(data) {
            console.log(data);
        },
        onclose: function() {
            // close modal
        },
        customizations: {
            title: "{{$data['title']}}",
            description: "{{$data['description']}}",
            logo: "{{$data['logo']}}"
        },
        callback: function(data) { // specified callback function
            console.log(data);
        },
    });
}
document.getElementById('enviar').click();
</script>