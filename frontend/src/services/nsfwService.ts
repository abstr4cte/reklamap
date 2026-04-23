const NSFWJS_CDN = 'https://cdn.jsdelivr.net/npm/nsfwjs/dist/nsfwjs.min.js'

let scriptLoaded = false
let model: any = null

function loadScript(): Promise<void> {
    if (scriptLoaded || (window as any).nsfwjs) {
        scriptLoaded = true
        return Promise.resolve()
    }
    return new Promise((resolve, reject) => {
        const script = document.createElement('script')
        script.src = NSFWJS_CDN
        script.onload = () => { scriptLoaded = true; resolve() }
        script.onerror = () => reject(new Error('Failed to load nsfwjs'))
        document.head.appendChild(script)
    })
}

export const nsfwService = {
    async loadModel() {
        if (!model) {
            await loadScript()
            model = await (window as any).nsfwjs.load('https://unpkg.com/nsfwjs/quant_nsfw_mobilenet/')
        }
        return model
    },

    async checkImage(file: File): Promise<{ isSafe: boolean; probability: number; className: string }> {
        try {
            const currentModel = await this.loadModel()

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
        } catch {
            return { isSafe: true, probability: 0, className: 'Error' }
        }
    }
}
