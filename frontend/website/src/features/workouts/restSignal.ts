/**
 * The end of a rest, said with what the browser lets a page use: three short beeps, drawn rather
 * than loaded, and a buzz where the device has one. Where neither is allowed — an iPhone ignores
 * the buzz, a muted tab the beeps — the bar disappearing is the signal.
 */

let context: AudioContext | null = null

/**
 * A browser plays sound only once a page has been touched. Called from the tap that starts a rest,
 * so the end of it, a minute later and with no tap of its own, may be heard.
 */
export function unlockRestSignal(): void {
    try {
        context ??= new AudioContext()
        void context.resume()
    } catch {
        // No sound here: the buzz and the bar still say it.
    }
}

export function playRestSignal(): void {
    navigator.vibrate?.([200, 100, 200])

    if (context === null || context.state !== 'running') {
        return
    }

    const start = context.currentTime
    for (const offset of [0, 0.25, 0.5]) {
        const oscillator = context.createOscillator()
        const gain = context.createGain()
        oscillator.frequency.value = 880
        // A short fade each way: a tone cut dead clicks.
        gain.gain.setValueAtTime(0, start + offset)
        gain.gain.linearRampToValueAtTime(0.3, start + offset + 0.02)
        gain.gain.linearRampToValueAtTime(0, start + offset + 0.15)
        oscillator.connect(gain).connect(context.destination)
        oscillator.start(start + offset)
        oscillator.stop(start + offset + 0.16)
    }
}
