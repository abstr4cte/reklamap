import * as tf from '@tensorflow/tfjs'
import * as nsfwjs from 'nsfwjs'

let model: nsfwjs.NSFWJS | null = null

export const loadModel = async () => {
    if (model) return model
    model = await nsfwjs.load()
    return model
}

export const checkImage = async (file: File): Promise<{ isSafe: boolean; probability: number; className: string }> => {
    try {
        const model = await loadModel()
        const img = document.createElement('img')
        const objectUrl = URL.createObjectURL(file)

        return new Promise((resolve) => {
            img.onload = async () => {
                const predictions = await model.classify(img)
                URL.revokeObjectURL(objectUrl)

                // Check for Porn or Hentai with high probability
                const nsfwPrediction = predictions.find(p => p.className === 'Porn' || p.className === 'Hentai')

                if (nsfwPrediction && nsfwPrediction.probability > 0.6) {
                    resolve({
                        isSafe: false,
                        probability: nsfwPrediction.probability,
                        className: nsfwPrediction.className
                    })
                } else {
                    resolve({
                        isSafe: true,
                        probability: 0,
                        className: 'Neutral'
                    })
                }
            }
            img.src = objectUrl
        })
    } catch (error) {
        console.error('Error checking image:', error)
        // Default to safe if check fails to avoid blocking user
        return { isSafe: true, probability: 0, className: 'Error' }
    }
}
