<script setup lang="ts">
const nodes = [
    { x: 40, y: 34, r: 7 },
    { x: 172, y: 30, r: 7 },
    { x: 196, y: 92, r: 6 },
    { x: 58, y: 98, r: 6 },
];
</script>

<template>
    <svg
        viewBox="0 0 240 128"
        class="h-full w-full text-brand-blue"
        aria-hidden="true"
    >
        <g stroke="currentColor" stroke-opacity="0.45" stroke-width="1">
            <line
                v-for="(node, index) in nodes"
                :key="index"
                x1="120"
                y1="64"
                :x2="node.x"
                :y2="node.y"
                stroke-dasharray="3 4"
            />
        </g>
        <circle
            v-for="(node, index) in nodes"
            :key="`pulse-${index}`"
            r="2.2"
            class="fill-[#ff8a5b]"
        >
            <animateMotion
                :dur="`${2.4 + index * 0.35}s`"
                repeatCount="indefinite"
                :path="`M120 64 L${node.x} ${node.y}`"
            />
        </circle>
        <g v-for="(node, index) in nodes" :key="`node-${index}`">
            <circle
                :cx="node.x"
                :cy="node.y"
                :r="node.r + 5"
                fill="currentColor"
                fill-opacity="0.12"
            />
            <rect
                :x="node.x - node.r"
                :y="node.y - node.r"
                :width="node.r * 2"
                :height="node.r * 2"
                rx="2.5"
                fill="currentColor"
                fill-opacity="0.85"
            />
        </g>
        <circle
            cx="120"
            cy="64"
            r="20"
            fill="currentColor"
            fill-opacity="0.14"
        />
        <circle cx="120" cy="64" r="13" fill="currentColor" />
        <text
            x="120"
            y="68"
            text-anchor="middle"
            class="fill-white text-[10px] font-semibold"
        >
            FT
        </text>
    </svg>
</template>
