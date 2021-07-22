export type SurfaceStyle = {
  strokeColor: string
  fillColor: string
}

const surfaceStyleA: SurfaceStyle = {
  strokeColor: '#34a853',
  fillColor: '#34a853'
}

const surfaceStyleB: SurfaceStyle = {
  strokeColor: '#ff994d',
  fillColor: '#ff994d'
}

const surfaceStyleC: SurfaceStyle = {
  strokeColor: '#999999',
  fillColor: '#999999'
}

const surfaceStyleD: SurfaceStyle = {
  strokeColor: '#34a853',
  fillColor: '#34a853'
}
const surfaceStyleE: SurfaceStyle = {
  strokeColor: '#999999',
  fillColor: '#999999'
}
const surfaceStyleF: SurfaceStyle = {
  strokeColor: '#906ba3',
  fillColor: '#906ba3'
}
const surfaceStyleG: SurfaceStyle = {
  strokeColor: '#adb431',
  fillColor: '#adb431'
}
const surfaceStyleH: SurfaceStyle = {
  strokeColor: '#46bdc6',
  fillColor: '#46bdc6'
}
const surfaceStyleI: SurfaceStyle = {
  strokeColor: '#ef7b72',
  fillColor: '#ef7b72'
}
const surfaceStyleJ: SurfaceStyle = {
  strokeColor: '#7caaf7',
  fillColor: '#7caaf7'
}
const surfaceStyleK: SurfaceStyle = {
  strokeColor: '#f8d776',
  fillColor: '#f8d776'
}
const surfaceStyleL: SurfaceStyle = {
  strokeColor: '#fc4fd0',
  fillColor: '#fc4fd0'
}
const surfaceStyleM: SurfaceStyle = {
  strokeColor: '#71c387',
  fillColor: '#71c387'
}
const surfaceStyleN: SurfaceStyle = {
  strokeColor: '#ff994d',
  fillColor: '#ff994d'
}
const surfaceStyleO: SurfaceStyle = {
  strokeColor: '#555555',
  fillColor: '#555555'
}
const surfaceStyleP: SurfaceStyle = {
  strokeColor: '#bbbbbb',
  fillColor: '#bbbbbb'
}
const surfaceStyleQ: SurfaceStyle = {
  strokeColor: '#0f2f4a',
  fillColor: '#0f2f4a'
}
const surfaceStyleR: SurfaceStyle = {
  strokeColor: '#8e2f7a',
  fillColor: '#8e2f7a'
}
const surfaceStyleS: SurfaceStyle = {
  strokeColor: '#222222',
  fillColor: '#222222'
}

export function getGraphSeriesStyle(seriesLength: number) {
  switch (seriesLength) {
    case 1:
      return [surfaceStyleB]
    case 2:
      return [surfaceStyleA, surfaceStyleC]
    case 3:
      return [surfaceStyleA, surfaceStyleB, surfaceStyleC]
    default:
      return [
        surfaceStyleA,
        surfaceStyleB,
        surfaceStyleC,
        surfaceStyleD,
        surfaceStyleE,
        surfaceStyleF,
        surfaceStyleG,
        surfaceStyleH,
        surfaceStyleI,
        surfaceStyleJ,
        surfaceStyleK,
        surfaceStyleL,
        surfaceStyleM,
        surfaceStyleN,
        surfaceStyleO,
        surfaceStyleP,
        surfaceStyleQ,
        surfaceStyleR,
        surfaceStyleS
      ]
  }
}
