
import * as nsfwjs from 'nsfwjs'

// Singleton instance
let model: nsfwjs.NSFWJS | null = null

export const nsfwService = {
    async loadModel() {
        if (!model) {
            // Load the model from a public CDN to avoid needing to bundle it locally if not present
            // or just let nsfwjs load its default model from unpkg/jsdelivr
            try {
                model = await nsfwjs.load()
            } catch (error) {
                console.error('Failed to load NSFW model:', error)
                throw error
            }
        }
        return model
    },

    async checkImage(file: File): Promise<{ isSafe: boolean; probability: number; className: string }> {
        try {
            const currentModel = await this.loadModel()

            // Create an HTMLImageElement from the file
            const img = document.createElement('img')
            const objectUrl = URL.createObjectURL(file)

            return new Promise((resolve, reject) => {
                img.onload = async () => {
                    try {
                        const predictions = await currentModel.classify(img)
                        URL.revokeObjectURL(objectUrl)

                        const thresholds: Record<string, number> = {
                            'Porn': 0.4,
                            'Hentai': 0.4,
                            'Sexy': 0.6
                        }

                        let unsafePrediction = null

                        for (const prediction of predictions) {
                            const threshold = thresholds[prediction.className]
                            if (threshold !== undefined && prediction.probability > threshold) {
                                unsafePrediction = prediction
                                break
                            }
                        }

                        if (unsafePrediction) {
                            resolve({
                                isSafe: false,
                                probability: unsafePrediction.probability,
                                className: unsafePrediction.className
                            })
                        } else {
                            resolve({
                                isSafe: true,
                                probability: predictions[0].probability,
                                className: predictions[0].className
                            })
                        }
                    } catch (error) {
                        URL.revokeObjectURL(objectUrl)
                        reject(error)
                    }
                }

                img.onerror = () => {
                    URL.revokeObjectURL(objectUrl)
                    reject(new Error('Failed to load image for classification'))
                }

                img.src = objectUrl
            })
        } catch (error) {
            console.error('NSFW check failed:', error)
            // Fail open or closed? Let's fail open (allow image) but log error to avoid blocking user if model fails
            // Or fail closed (block image)? Safety first -> maybe return true (safe) but warn?
            // Let's return safe=true if model fails to load, to not break the app.
            return { isSafe: true, probability: 0, className: 'Error' }
        }
    }
}
