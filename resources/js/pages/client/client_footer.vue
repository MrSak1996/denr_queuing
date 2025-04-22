<script lang="ts" setup>
import denr_logo from '../../../images/denr_logo.png';

</script>
<template>
    <div class="grid grid-cols-4 gap-4">
                <div class="col-span-4 flex items-center justify-between rounded border-t-[5px] border-green-600 bg-blue-950 px-6 py-4">
                    <!-- Left: Logo -->
                    <div class="flex items-center gap-4">
                        <img :src="denr_logo" alt="Logo" class="h-auto w-[100px]" />
                        <div class="text-lg font-semibold leading-tight text-white">
                            Department of Environment and Natural Resources<br />
                            Regional Office IV-A (CALABARZON)
                        </div>
                    </div>

                    <!-- Right: Copyright -->
                    <div class="text-right text-sm text-white">© 2025 PMD-RICT. All rights reserved.</div>
                </div>
            </div>
</template>