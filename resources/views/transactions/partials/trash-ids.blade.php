{{-- Id terpilih untuk form aksi massal.

     Checkbox di tabel sudah membawa `ids[]`, tapi ia ada di dalam #riwayatTable
     yang TIDAK berada di dalam form toolbar — jadi isinya tidak ikut submit.
     Field ini yang menjembataninya, ditulis Alpine dari state `selection`.

     Formatnya CSV (dipisah koma) dan controller mem-parsing ulang; memakai
     satu hidden input jauh lebih ringan daripada menyalin ulang seluruh blok
     checkbox ke setiap form. --}}
<input type="hidden" name="ids" :value="selection.join(',')">
